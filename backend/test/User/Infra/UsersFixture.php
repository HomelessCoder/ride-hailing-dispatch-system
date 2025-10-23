<?php

declare(strict_types=1);

namespace App\Test\User\Infra;

use App\Shared\Id;
use App\Test\Fixture\User\Alice;
use App\Test\Fixture\User\Bob;
use App\User\User;

trait UsersFixture
{
    protected function addTestUsersSet(): void
    {
        $this->addTestUser(new Alice());
        $this->addTestUser(new Bob());
    }

    protected function addTestUser(
        User $user,
    ): void {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare(
            "INSERT INTO users (id, name, email) VALUES (:id, :name, :email)",
        );
        $stmt->execute([
            'id' => (string)$user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    protected function makeUser(
        Id $id,
        string $name,
        string $email,
    ): User {
        return new User(
            id: $id,
            name: $name,
            email: $email,
        );
    }
}
