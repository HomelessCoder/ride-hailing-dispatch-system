<?php

declare(strict_types=1);

namespace App\Test\Driver\Infra;

use App\Driver\Infra\DriverRepository;
use App\Shared\Id;
use App\Test\DatabaseTestCase;
use App\Test\Fixture\Driver\Charlie;

final class DriverRepositoryTest extends DatabaseTestCase
{
    use DriversFixture;
    private DriverRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DriverRepository(self::getPdo());

        // Clean slate for each test
        $this->cleanupTables(['drivers']);
    }

    public function testFindAllReturnsAllDrivers(): void
    {
        $this->addTestDriversSet();
        $drivers = $this->repository->findAll();

        self::assertCount(4, $drivers);
    }

    public function testFindReturnsDriver(): void
    {
        $this->addTestDriversSet();
        $charlie = new Charlie();
        $driver = $this->repository->find($charlie->id);

        self::assertNotNull($driver);
        self::assertEquals($charlie->name, $driver->name);
        self::assertEquals($charlie->email, $driver->email);
        self::assertEquals($charlie->status, $driver->status);
    }

    public function testFindReturnsNullForNonexistentDriver(): void
    {
        $driver = $this->repository->find(Id::generate());

        self::assertNull($driver);
    }
}
