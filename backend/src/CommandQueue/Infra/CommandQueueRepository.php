<?php

declare(strict_types=1);

namespace App\CommandQueue\Infra;

use App\CommandQueue\CommandQueue;
use App\CommandQueue\CommandQueueStatus;
use App\Infra\AbstractRepository;
use App\Shared\Id;
use InvalidArgumentException;
use PDO;
use Pdo\Pgsql;
use RuntimeException;

/**
 * @extends AbstractRepository<CommandQueue>
 */
class CommandQueueRepository extends AbstractRepository
{
    public function __construct(
        Pgsql $connection,
    ) {
        parent::__construct($connection);
    }

    public function enqueue(object $command): Id
    {
        $commandQueue = new CommandQueue(
            id: Id::generate(),
            status: CommandQueueStatus::Pending,
            type: get_class($command),
            payload: serialize($command),
            attempts: 0,
            lastError: null,
            createdAt: date('Y-m-d H:i:s'),
            updatedAt: date('Y-m-d H:i:s'),
        );

        if ($this->insert($commandQueue) === true) {
            $this->notify('command_queue');

            return $commandQueue->id;
        }

        throw new RuntimeException('Failed to enqueue command');
    }

    /**
     * Enqueue a command to be executed after a specific delay in seconds.
     * Uses the attempts field to calculate backoff: 2^attempts seconds.
     * For example: delaySeconds=30 will use attempts=5 (2^5=32 seconds)
     */
    public function enqueueDelayed(object $command, int $delaySeconds): Id
    {
        // Calculate attempts needed for the desired delay using exponential backoff
        // 2^attempts >= delaySeconds, so attempts = ceil(log2(delaySeconds))
        $attempts = max(0, (int)ceil(log($delaySeconds, 2)));

        // Set updatedAt in the past to achieve the delay
        // updatedAt + 2^attempts should be approximately NOW + delaySeconds
        $updatedAt = date('Y-m-d H:i:s', time());

        $commandQueue = new CommandQueue(
            id: Id::generate(),
            status: CommandQueueStatus::Pending,
            type: get_class($command),
            payload: serialize($command),
            attempts: $attempts,
            lastError: null,
            createdAt: date('Y-m-d H:i:s'),
            updatedAt: $updatedAt,
        );

        if ($this->insert($commandQueue) === true) {
            $this->notify('command_queue');

            return $commandQueue->id;
        }

        throw new RuntimeException('Failed to enqueue delayed command');
    }

    /**
     * @return CommandQueue[]
     */
    public function getPendingCommandsForProcessing(string $type = 'any', int $limit = 1): array
    {
        $typeFilter = $type === 'any' ? '' : ' AND ' . Schema::Type->value . ' = :type';
        // Exponential backoff: 2^attempts seconds delay
        // attempts=0: no delay, attempts=1: 2s, attempts=2: 4s, attempts=3: 8s
        $sql = sprintf(
            'SELECT * FROM %s 
             WHERE %s = :status 
                %s
               AND %s + (POWER(2, %s) || \' seconds\')::interval <= NOW()
             ORDER BY %s, %s 
             LIMIT :limit 
             FOR UPDATE SKIP LOCKED',
            $this->tableName(),
            Schema::Status->value,
            $typeFilter,
            Schema::UpdatedAt->value,
            Schema::Attempts->value,
            Schema::CreatedAt->value,
            Schema::Id->value,
        );

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('status', CommandQueueStatus::Pending->value, PDO::PARAM_STR);

        if ($typeFilter !== '') {
            $stmt->bindValue('type', $type, PDO::PARAM_STR);
        }

        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn (array $data) => $this->hydrate($data), $results);
    }

    public function updateStatus(Id $id, CommandQueueStatus $status, ?string $lastError = null): void
    {
        // Only increment attempts when there's an error (failed or retrying)
        if ($lastError !== null) {
            $sql = sprintf(
                'UPDATE %s SET %s = :status, %s = :last_error, %s = %s + 1, %s = NOW() WHERE %s = :id',
                $this->tableName(),
                Schema::Status->value,
                Schema::LastError->value,
                Schema::Attempts->value,
                Schema::Attempts->value,
                Schema::UpdatedAt->value,
                Schema::Id->value,
            );
        } else {
            $sql = sprintf(
                'UPDATE %s SET %s = :status, %s = :last_error, %s = NOW() WHERE %s = :id',
                $this->tableName(),
                Schema::Status->value,
                Schema::LastError->value,
                Schema::UpdatedAt->value,
                Schema::Id->value,
            );
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('status', $status->value, PDO::PARAM_STR);
        $stmt->bindValue('last_error', $lastError, PDO::PARAM_STR);
        $stmt->bindValue('id', $id->__toString(), PDO::PARAM_STR);
        $stmt->execute();
    }

    // @phpstan-ignore-next-line
    public function listen(int $timeoutMs): array|false
    {
        $this->connection->exec('LISTEN "command_queue"');

        return $this->connection->pgsqlGetNotify(PDO::FETCH_ASSOC, $timeoutMs);
    }

    protected function hydrate(array $data): object
    {
        return new CommandQueue(
            id: Id::fromString($data[Schema::Id->value]),
            status: CommandQueueStatus::from($data[Schema::Status->value]),
            type: $data[Schema::Type->value],
            payload: $data[Schema::Payload->value],
            attempts: (int)$data[Schema::Attempts->value],
            lastError: $data[Schema::LastError->value] ?? null,
            createdAt: $data[Schema::CreatedAt->value],
            updatedAt: $data[Schema::UpdatedAt->value],
        );
    }

    protected function dehydrate(object $entity): array
    {
        // @phpstan-ignore-next-line -- runtime check
        if (!$entity instanceof CommandQueue) {
            throw new InvalidArgumentException('Expected instance of CommandQueue');
        }

        return [
            Schema::Id->value => $entity->id->__toString(),
            Schema::Status->value => $entity->status->value,
            Schema::Type->value => $entity->type,
            Schema::Payload->value => $entity->payload,
            Schema::Attempts->value => $entity->attempts,
            Schema::LastError->value => $entity->lastError,
            Schema::CreatedAt->value => $entity->createdAt,
            Schema::UpdatedAt->value => $entity->updatedAt,
        ];
    }

    protected function tableName(): string
    {
        return Schema::getTableName();
    }

    private function notify(string $channel): void
    {
        // use PG SQL notify to signal a new command is available
        $this->connection->exec(sprintf('NOTIFY "%s"', $channel));
    }
}
