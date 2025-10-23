<?php

declare(strict_types=1);

namespace App\User\Infra;

use App\Infra\AbstractRepository;
use App\Shared\Id;
use App\User\User;

/**
 * @extends AbstractRepository<User>
 */
class UserRepository extends AbstractRepository
{
    protected function tableName(): string
    {
        return Schema::getTableName();
    }

    protected function hydrate(array $data): User
    {
        return new User(
            id: Id::fromString($data[Schema::Id->value]),
            name: $data[Schema::Name->value],
            email: $data[Schema::Email->value],
        );
    }

    protected function dehydrate(object $entity): array
    {
        return [
            Schema::Id->value => (string)$entity->id,
            Schema::Name->value => $entity->name,
            Schema::Email->value => $entity->email,
        ];
    }
}
