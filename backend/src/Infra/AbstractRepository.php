<?php

declare(strict_types=1);

namespace App\Infra;

use App\Shared\Id;
use PDO;

/**
 * @template T of object
 *
 * @implements IRepository<T>
 */
abstract class AbstractRepository implements IRepository
{
    public function __construct(
        protected readonly PDO $connection,
    ) {
    }

    public function beginTransaction(): void
    {
        if (!$this->connection->inTransaction()) {
            $this->connection->beginTransaction();
            $this->connection->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        }
    }

    public function commit(): void
    {
        if ($this->connection->inTransaction()) {
            $this->connection->commit();
        }
    }

    public function rollback(): void
    {
        if ($this->connection->inTransaction()) {
            $this->connection->rollBack();
        }
    }

    /**
     * @param array<string,mixed> $where
     * @return array<T>
     */
    public function findAll(
        array $where = [],
        bool $forUpdate = false,
    ): array {
        $sql = $this->getSelectAllSql();

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', array_map(fn ($field) => "$field = :$field", array_keys($where)));
        }

        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $statement = $this->connection->prepare($sql);
        $statement->execute($where);
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        $entities = [];

        foreach ($results as $data) {
            $entities[] = $this->hydrate($data);
        }

        return $entities;
    }

    public function find(Id $id, bool $forUpdate = false): ?object
    {
        $statement = $this->connection->prepare($this->getSelectAllSql() . ' WHERE id = :id' . ($forUpdate ? ' FOR UPDATE' : ''));
        $statement->execute(['id' => (string)$id]);
        $data = $statement->fetch(PDO::FETCH_ASSOC);

        if ($data === false) {
            return null;
        }

        return $this->hydrate($data);
    }

    public function update(object $entity): bool
    {
        $data = $this->dehydrate($entity);
        $columns = array_keys($data);

        $columnsToUpdate = array_filter($columns, static fn ($col): bool => $col !== 'id');
        $setClause = [];
        // if col starts with ST_ then it's a raw sql expression, don't use placeholder
        foreach ($columnsToUpdate as $col) {
            if (is_string($data[$col]) && str_starts_with($data[$col], 'ST_')) {
                $setClause[] = sprintf('%s = %s', $col, $data[$col]);
                unset($data[$col]);
            } else {
                $setClause[] = sprintf('%s = :%s', $col, $col);
            }
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE id = :id',
            $this->tableName(),
            implode(', ', $setClause),
        );

        $statement = $this->connection->prepare($sql);

        return $statement->execute($data);
    }

    public function insert(object $entity): bool
    {
        $data = $this->dehydrate($entity);
        $columns = array_keys($data);
        $placeholders = [];
        // if col starts with ST_ then it's a raw sql expression, don't use placeholder
        foreach ($columns as $col) {
            if (is_string($data[$col]) && str_starts_with($data[$col], 'ST_')) {
                $placeholders[] = $data[$col];
                unset($data[$col]);
            } else {
                $placeholders[] = ':' . $col;
            }
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->tableName(),
            implode(', ', $columns),
            implode(', ', $placeholders),
        );

        $statement = $this->connection->prepare($sql);

        return $statement->execute($data);
    }

    protected function getSelectAllSql(): string
    {
        return 'SELECT * FROM ' . $this->tableName();
    }

    /**
     * @param array<string,mixed> $data
     * @return T
     */
    abstract protected function hydrate(array $data): object;

    /**
     * @param T $entity
     * @return array<string, mixed>
     */
    abstract protected function dehydrate(object $entity): array;
    abstract protected function tableName(): string;
}
