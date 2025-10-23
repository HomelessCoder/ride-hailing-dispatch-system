<?php

declare(strict_types=1);

namespace App\Infra;

use App\Shared\Id;

/**
 * @template T of object
 */
interface IRepository
{
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollback(): void;

    /**
     * @return T|null
     */
    public function find(Id $id, bool $forUpdate = false): ?object;

    /**
     * @param T $entity
     */
    public function update(object $entity): bool;

    /**
     * @param T $entity
     */
    public function insert(object $entity): bool;
}
