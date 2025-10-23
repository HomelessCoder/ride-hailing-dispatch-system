<?php

declare(strict_types=1);

namespace App\Test\Driver;

use App\Driver\DriverService;
use App\Driver\Exception\DriverNotFoundException;
use App\Driver\Infra\DriverRepository;
use App\Driver\Status;
use App\Shared\Id;
use App\Shared\Location\Location;
use App\Test\DatabaseTestCase;
use App\Test\Driver\Infra\DriversFixture;
use App\Test\Fixture\Driver\Charlie;
use App\Test\Fixture\Driver\Frank;

final class DriverServiceTest extends DatabaseTestCase
{
    use DriversFixture;

    private DriverService $service;
    private DriverRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DriverRepository(self::getPdo());
        $this->service = new DriverService($this->repository);

        // Clean slate for each test
        $this->cleanupTables(['drivers']);
    }

    public function testUpdateStatusChangesDriverStatus(): void
    {
        $this->addTestDriversSet();
        $charlie = new Charlie();

        // Charlie is initially available
        $driver = $this->repository->find($charlie->id);
        self::assertNotNull($driver);
        self::assertEquals(Status::Available, $driver->status);

        // Update status to busy
        $updatedDriver = $this->service->updateStatus($charlie->id, Status::Busy);

        self::assertEquals(Status::Busy, $updatedDriver->status);
        self::assertEquals($charlie->id, $updatedDriver->id);
        self::assertEquals($charlie->name, $updatedDriver->name);
        self::assertEquals($charlie->email, $updatedDriver->email);

        // Verify change persisted
        $persistedDriver = $this->repository->find($charlie->id);
        self::assertNotNull($persistedDriver);
        self::assertEquals(Status::Busy, $persistedDriver->status);
    }

    public function testUpdateStatusThrowsExceptionForNonexistentDriver(): void
    {
        $this->expectException(DriverNotFoundException::class);
        $this->expectExceptionMessage('Driver with ID');

        $this->service->updateStatus(Id::generate(), Status::Busy);
    }

    public function testUpdateLocationChangesDriverLocation(): void
    {
        $this->addTestDriversSet();
        $frank = new Frank();

        $newLocation = new Location(latitude: 40.7128, longitude: -74.0060); // New York
        $updatedDriver = $this->service->updateLocation($frank->id, $newLocation);

        self::assertEquals($newLocation->latitude, $updatedDriver->currentLocation->latitude);
        self::assertEquals($newLocation->longitude, $updatedDriver->currentLocation->longitude);
        self::assertEquals($frank->status, $updatedDriver->status);

        // Verify change persisted
        $persistedDriver = $this->repository->find($frank->id);
        self::assertNotNull($persistedDriver);
        self::assertEquals($newLocation->latitude, $persistedDriver->currentLocation->latitude);
        self::assertEquals($newLocation->longitude, $persistedDriver->currentLocation->longitude);
    }

    public function testUpdateStatusAndLocationUpdatesBoth(): void
    {
        $this->addTestDriversSet();
        $charlie = new Charlie();

        $newLocation = new Location(latitude: 51.5074, longitude: -0.1278); // London
        $newStatus = Status::Offline;

        $updatedDriver = $this->service->updateStatusAndLocation($charlie->id, $newStatus, $newLocation);

        self::assertEquals($newStatus, $updatedDriver->status);
        self::assertEquals($newLocation->latitude, $updatedDriver->currentLocation->latitude);
        self::assertEquals($newLocation->longitude, $updatedDriver->currentLocation->longitude);

        // Verify changes persisted
        $persistedDriver = $this->repository->find($charlie->id);
        self::assertNotNull($persistedDriver);
        self::assertEquals($newStatus, $persistedDriver->status);
        self::assertEquals($newLocation->latitude, $persistedDriver->currentLocation->latitude);
    }

    public function testSetAvailableSetsDriverStatusToAvailable(): void
    {
        $this->addTestDriversSet();
        $frank = new Frank();

        // Frank is initially offline
        $initialDriver = $this->repository->find($frank->id);
        self::assertNotNull($initialDriver);
        self::assertEquals(Status::Offline, $initialDriver->status);

        $updatedDriver = $this->service->setAvailable($frank->id);

        self::assertEquals(Status::Available, $updatedDriver->status);

        $persistedDriver = $this->repository->find($frank->id);
        self::assertNotNull($persistedDriver);
        self::assertEquals(Status::Available, $persistedDriver->status);
    }

    public function testSetBusySetsDriverStatusToBusy(): void
    {
        $this->addTestDriversSet();
        $charlie = new Charlie();

        // Charlie is initially available
        $initialDriver = $this->repository->find($charlie->id);
        self::assertNotNull($initialDriver);
        self::assertEquals(Status::Available, $initialDriver->status);

        $updatedDriver = $this->service->setBusy($charlie->id);

        self::assertEquals(Status::Busy, $updatedDriver->status);

        $persistedDriver = $this->repository->find($charlie->id);
        self::assertNotNull($persistedDriver);
        self::assertEquals(Status::Busy, $persistedDriver->status);
    }

    public function testSetOfflineSetsDriverStatusToOffline(): void
    {
        $this->addTestDriversSet();
        $charlie = new Charlie();

        $updatedDriver = $this->service->setOffline($charlie->id);

        self::assertEquals(Status::Offline, $updatedDriver->status);

        $persistedDriver = $this->repository->find($charlie->id);
        self::assertNotNull($persistedDriver);
        self::assertEquals(Status::Offline, $persistedDriver->status);
    }

    public function testGetDriverReturnsDriver(): void
    {
        $this->addTestDriversSet();
        $charlie = new Charlie();

        $driver = $this->service->getDriver($charlie->id);

        self::assertNotNull($driver);
        self::assertEquals($charlie->id, $driver->id);
        self::assertEquals($charlie->name, $driver->name);
    }

    public function testGetDriverReturnsNullForNonexistentDriver(): void
    {
        $driver = $this->service->getDriver(Id::generate());

        self::assertNull($driver);
    }
}
