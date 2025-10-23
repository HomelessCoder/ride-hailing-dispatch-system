<?php

declare(strict_types=1);

namespace App\CommandQueue;

use App\CommandQueue\Infra\CommandQueueRepository;
use App\CommandQueue\CommandQueueStatus;
use Throwable;
use App\Shared\ICommandHandler;
use App\CommandQueue\ICommandQueueWorker;
use App\CommandQueue\CommandQueue;

/**
 * @template T of object
 */
class Worker implements ICommandQueueWorker
{
    /**
     * @param ICommandHandler<T> $commandHandler
     */
    public function __construct(
        protected readonly CommandQueueRepository $commandQueueRepo,
        protected readonly ICommandHandler $commandHandler,
    ) {
    }

    public function process(): int
    {
        try {
            $this->commandQueueRepo->beginTransaction();

            $commandsToProcess = $this->getCommandsToProcess();
            $processedCount = 0;

            foreach ($commandsToProcess as $commandQueueItem) {
                $command = unserialize($commandQueueItem->payload);

                try {
                    $this->commandQueueRepo->updateStatus($commandQueueItem->id, CommandQueueStatus::Processing);
                    $this->commandHandler->handle($command);
                    $this->commandQueueRepo->updateStatus($commandQueueItem->id, CommandQueueStatus::Completed);
                    $processedCount++;
                } catch (Throwable $e) {
                    $newStatus = $commandQueueItem->attempts >= 5
                        ? CommandQueueStatus::Failed
                        : CommandQueueStatus::Pending;

                    $this->commandQueueRepo->updateStatus(
                        $commandQueueItem->id,
                        $newStatus,
                        $e->getMessage(),
                    );

                    continue;
                }
            }

            $this->commandQueueRepo->commit();

            return $processedCount;
        } catch (Throwable $e) {
            $this->commandQueueRepo->rollback();
            throw $e;
        }
    }

    /**
     * @return CommandQueue[]
     */
    protected function getCommandsToProcess(): array
    {
        return $this->commandQueueRepo->getPendingCommandsForProcessing('any', 5);
    }
}
