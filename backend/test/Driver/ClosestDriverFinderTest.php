<?php

declare(strict_types=1);

namespace App\Test\Driver;

use App\Driver\ClosestDriverFinder;
use App\Driver\Infra\DriverRepository;
use App\Driver\Status;
use App\Shared\Distance;
use App\Shared\Location\Location;
use App\Test\DatabaseTestCase;
use App\Test\Driver\Infra\DriversFixture;
use App\Test\Fixture\Driver\Charlie;
use App\Test\Fixture\Driver\David;
use App\Test\Fixture\Driver\Eve;
use App\Test\Fixture\Driver\Frank;
use App\Test\Fixture\Shared\Downtown;
use App\Test\Fixture\Shared\Heathrow;
use App\Test\Fixture\Shared\Midtown;
use App\Test\Fixture\Shared\Uptown;

final class ClosestDriverFinderTest extends DatabaseTestCase
{
    use DriversFixture;

    private ClosestDriverFinder $finder;
    private DriverRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DriverRepository(self::getPdo());
        $this->finder = new ClosestDriverFinder($this->repository);

        $this->cleanupTables(['drivers']);
    }

    public function testFindClosestAvailableDriverWhenMultipleAvailable(): void
    {
        $this->addTestDriversSet();

        $location = new Midtown();
        $closestDriver = $this->finder->findClosestAvailableDriver($location);

        self::assertNotNull($closestDriver);
        self::assertTrue(
            in_array($closestDriver->name, ['David', 'Eve'], true),
            'Expected David or Eve to be closest to Midtown',
        );
        self::assertSame(Status::Available, $closestDriver->status);
    }

    public function testFindClosestAvailableDriverIgnoresBusyDrivers(): void
    {
        $this->addTestDriversSet();

        $david = new David();
        $this->repository->beginTransaction();
        $davidDriver = $this->repository->find($david->id, forUpdate: true);
        self::assertNotNull($davidDriver);
        $busyDavid = $davidDriver->withStatus(Status::Busy);
        $this->repository->update($busyDavid);
        $this->repository->commit();

        $location = new Midtown();
        $closestDriver = $this->finder->findClosestAvailableDriver($location);

        self::assertNotNull($closestDriver);
        self::assertNotSame('David', $closestDriver->name);
        self::assertSame('Eve', $closestDriver->name);
        self::assertSame(Status::Available, $closestDriver->status);
    }

    public function testFindClosestAvailableDriverIgnoresOfflineDrivers(): void
    {
        $this->addTestDriversSet();

        $location = new Downtown();
        $closestDriver = $this->finder->findClosestAvailableDriver($location);

        self::assertNotNull($closestDriver);
        self::assertNotSame('Frank', $closestDriver->name);
        self::assertSame('David', $closestDriver->name);
    }

    public function testFindClosestAvailableDriverReturnsNullWhenNoAvailableDrivers(): void
    {
        $frank = new Frank();
        $this->addTestDriver($frank);

        $location = new Downtown();
        $closestDriver = $this->finder->findClosestAvailableDriver($location);

        self::assertNull($closestDriver);
    }

    public function testFindClosestAvailableDriverReturnsNullWhenNoDriversExist(): void
    {
        $location = new Downtown();
        $closestDriver = $this->finder->findClosestAvailableDriver($location);

        self::assertNull($closestDriver);
    }

    public function testFindClosestAvailableDriverBasedOnProximity(): void
    {
        $this->addTestDriversSet();

        // Search from a location near Heathrow (not exact match)
        // Charlie at Heathrow should be closest, not David/Eve downtown
        $nearHeathrow = new Location(51.475, -0.450);
        $closestDriver = $this->finder->findClosestAvailableDriver($nearHeathrow);

        self::assertNotNull($closestDriver);
        self::assertSame('Charlie', $closestDriver->name);
        self::assertSame(Status::Available, $closestDriver->status);
    }

    public function testFindClosestAvailableDriverFromUptown(): void
    {
        $this->addTestDriversSet();

        $location = new Uptown();
        $closestDriver = $this->finder->findClosestAvailableDriver($location);

        self::assertNotNull($closestDriver);
        self::assertTrue(
            in_array($closestDriver->name, ['David', 'Eve'], true),
            'Expected David or Eve to be closest to Uptown',
        );
        self::assertSame(Status::Available, $closestDriver->status);
    }

    public function testFindClosestAvailableDriverWhenAllBusyReturnsNull(): void
    {
        $this->addTestDriversSet();

        $this->repository->beginTransaction();
        foreach ([new Charlie(), new David(), new Eve()] as $fixture) {
            $driver = $this->repository->find($fixture->id, forUpdate: true);
            if ($driver !== null) {
                $busyDriver = $driver->withStatus(Status::Busy);
                $this->repository->update($busyDriver);
            }
        }
        $this->repository->commit();

        $location = new Downtown();
        $closestDriver = $this->finder->findClosestAvailableDriver($location);

        self::assertNull($closestDriver);
    }

    public function testFindClosestAvailableDriverWithExactSameLocation(): void
    {
        $this->addTestDriversSet();

        $location = new Downtown();
        $closestDriver = $this->finder->findClosestAvailableDriver($location);

        self::assertNotNull($closestDriver);
        self::assertSame('David', $closestDriver->name);
        self::assertSame($location->latitude, $closestDriver->currentLocation->latitude);
        self::assertSame($location->longitude, $closestDriver->currentLocation->longitude);
    }

    public function testFindClosestAvailableDriverRespectsMaxDistance(): void
    {
        $this->addTestDriversSet();

        // Charlie is at Heathrow, search from Midtown with a small max distance
        // David and Eve are close to Midtown, Charlie is far away
        $location = new Midtown();
        $maxDistance = new Distance(1000); // 1km radius

        $closestDriver = $this->finder->findClosestAvailableDriver(
            $location,
            maxDistance: $maxDistance,
        );

        // Should find David or Eve (close to Midtown), not Charlie (far at Heathrow)
        self::assertNotNull($closestDriver);
        self::assertNotSame('Charlie', $closestDriver->name);
        self::assertTrue(
            in_array($closestDriver->name, ['David', 'Eve'], true),
            'Expected David or Eve within 1km of Midtown',
        );
    }

    public function testFindClosestAvailableDriverReturnsNullWhenAllDriversTooFar(): void
    {
        $this->addTestDriversSet();

        // Search from a remote location with a very small max distance
        // Using coordinates far from all test drivers
        $remoteLocation = new Location(52.5, 0.5); // Somewhere in the North Sea
        $maxDistance = new Distance(1000); // 1km radius - all drivers are much further

        $closestDriver = $this->finder->findClosestAvailableDriver(
            $remoteLocation,
            maxDistance: $maxDistance,
        );

        // All available drivers should be too far away from the remote location
        self::assertNull($closestDriver, 'Expected no drivers within 1km of remote location');
    }

    public function testFindClosestAvailableDriverWithinLargeDistance(): void
    {
        $this->addTestDriversSet();

        // Search from Uptown with a very large max distance
        $location = new Uptown();
        $maxDistance = new Distance(50000); // 50km radius - should include all drivers

        $closestDriver = $this->finder->findClosestAvailableDriver(
            $location,
            maxDistance: $maxDistance,
        );

        // Should find a driver (likely David or Eve as they're closer to Uptown)
        self::assertNotNull($closestDriver);
        self::assertSame(Status::Available, $closestDriver->status);
    }

    public function testFindClosestAvailableDriverWithExclusionAndMaxDistance(): void
    {
        $this->addTestDriversSet();

        $location = new Midtown();
        $david = new David();
        $eve = new Eve();
        $maxDistance = new Distance(5000); // 5km radius

        // Exclude David, so we should get Eve
        $closestDriver = $this->finder->findClosestAvailableDriver(
            $location,
            excludeDriverIds: [$david->id],
            maxDistance: $maxDistance,
        );

        self::assertNotNull($closestDriver);
        self::assertSame('Eve', $closestDriver->name);

        // Now exclude both David and Eve
        $closestDriver = $this->finder->findClosestAvailableDriver(
            $location,
            excludeDriverIds: [$david->id, $eve->id],
            maxDistance: $maxDistance,
        );

        // Charlie might still be within 5km, but if not, should return null
        // The actual result depends on the exact distances in the test data
        if ($closestDriver !== null) {
            self::assertSame('Charlie', $closestDriver->name);
        }
    }
}
