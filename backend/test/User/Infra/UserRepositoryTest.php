<?php

declare(strict_types=1);

namespace App\Test\User\Infra;

use App\Shared\Id;
use App\Test\DatabaseTestCase;
use App\Test\Fixture\User\Alice;
use App\User\Infra\UserRepository;

final class UserRepositoryTest extends DatabaseTestCase
{
    use UsersFixture;
    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository(self::getPdo());

        // Clean slate for each test
        $this->cleanupTables(['users']);
    }

    public function testFindAllReturnsAllUsers(): void
    {
        $this->addTestUsersSet();
        $users = $this->repository->findAll();

        self::assertCount(2, $users);
    }

    public function testFindReturnsUser(): void
    {
        $this->addTestUsersSet();
        $alice = new Alice();
        $user = $this->repository->find($alice->id);

        self::assertNotNull($user);
        self::assertEquals($alice->name, $user->name);
        self::assertEquals($alice->email, $user->email);
    }

    public function testFindReturnsNullForNonexistentUser(): void
    {
        $user = $this->repository->find(Id::generate());

        self::assertNull($user);
    }
}
