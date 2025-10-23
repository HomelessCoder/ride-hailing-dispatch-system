<?php

declare(strict_types=1);

namespace App\Test\Driver;

use App\Driver\Driver;
use App\Driver\Status;
use App\Shared\Id;
use App\Shared\Location\Location;
use PHPUnit\Framework\TestCase;

final class DriverTest extends TestCase
{
    private Driver $driver;

    protected function setUp(): void
    {
        $this->driver = new Driver(
            id: Id::generate(),
            name: 'Test Driver',
            email: 'test@example.com',
            currentLocation: new Location(latitude: 51.5074, longitude: -0.1278),
            status: Status::Available,
            updatedAt: new \DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }

    public function testWithStatusReturnsNewInstanceWithUpdatedStatus(): void
    {
        $newDriver = $this->driver->withStatus(Status::Busy);

        // Original unchanged
        self::assertEquals(Status::Available, $this->driver->status);

        // New instance has new status
        self::assertEquals(Status::Busy, $newDriver->status);

        // Other properties unchanged
        self::assertEquals($this->driver->id, $newDriver->id);
        self::assertEquals($this->driver->name, $newDriver->name);
        self::assertEquals($this->driver->email, $newDriver->email);
        self::assertEquals($this->driver->currentLocation, $newDriver->currentLocation);

        // Updated timestamp changed
        self::assertNotEquals($this->driver->updatedAt, $newDriver->updatedAt);
    }

    public function testWithCurrentLocationReturnsNewInstanceWithUpdatedLocation(): void
    {
        $newLocation = new Location(latitude: 40.7128, longitude: -74.0060);
        $newDriver = $this->driver->withCurrentLocation($newLocation);

        // Original unchanged
        self::assertEquals(51.5074, $this->driver->currentLocation->latitude);
        self::assertEquals(-0.1278, $this->driver->currentLocation->longitude);

        // New instance has new location
        self::assertEquals($newLocation, $newDriver->currentLocation);

        // Other properties unchanged
        self::assertEquals($this->driver->id, $newDriver->id);
        self::assertEquals($this->driver->status, $newDriver->status);
    }

    public function testWithStatusAndLocationReturnsNewInstanceWithBothUpdated(): void
    {
        $newLocation = new Location(latitude: 48.8566, longitude: 2.3522);
        $newDriver = $this->driver->withStatusAndLocation(Status::Offline, $newLocation);

        // New instance has both updated
        self::assertEquals(Status::Offline, $newDriver->status);
        self::assertEquals($newLocation, $newDriver->currentLocation);

        // Original unchanged
        self::assertEquals(Status::Available, $this->driver->status);
        self::assertEquals(51.5074, $this->driver->currentLocation->latitude);
    }

    public function testMarkAsAvailableSetsStatusToAvailable(): void
    {
        $busyDriver = $this->driver->withStatus(Status::Busy);
        $availableDriver = $busyDriver->markAsAvailable();

        self::assertEquals(Status::Available, $availableDriver->status);
    }

    public function testMarkAsBusySetsStatusToBusy(): void
    {
        $newDriver = $this->driver->markAsBusy();

        self::assertEquals(Status::Busy, $newDriver->status);
    }

    public function testMarkAsOfflineSetsStatusToOffline(): void
    {
        $newDriver = $this->driver->markAsOffline();

        self::assertEquals(Status::Offline, $newDriver->status);
    }

    public function testImmutabilityIsPreserved(): void
    {
        $originalId = $this->driver->id;
        $originalStatus = $this->driver->status;

        // Call various methods
        $this->driver->withStatus(Status::Busy);
        $this->driver->markAsOffline();
        $this->driver->withCurrentLocation(new Location(latitude: 0, longitude: 0));

        // Original is still unchanged
        self::assertEquals($originalId, $this->driver->id);
        self::assertEquals($originalStatus, $this->driver->status);
        self::assertEquals(51.5074, $this->driver->currentLocation->latitude);
    }
}
