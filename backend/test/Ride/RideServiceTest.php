<?php

declare(strict_types=1);

namespace App\Test\Ride;

use App\Ride\Exception\RideAlreadyAssignedException;
use App\Ride\Infra\RideRepository;
use App\Ride\Ride;
use App\Ride\RideService;
use App\Ride\State;
use App\Shared\Id;
use App\Test\DatabaseTestCase;
use App\Test\Driver\Infra\DriversFixture;
use App\Test\Fixture\Driver\Charlie;
use App\Test\Fixture\Driver\David;
use App\Test\Fixture\Ride\AliceRequestedRide;
use App\Test\Fixture\Shared\Downtown;
use App\Test\Fixture\Shared\Heathrow;
use App\Test\Fixture\User\Alice;
use App\Test\User\Infra\UsersFixture;

final class RideServiceTest extends DatabaseTestCase
{
    use DriversFixture;
    use UsersFixture;

    private RideService $service;
    private RideRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new RideRepository(self::getPdo());
        $this->service = new RideService($this->repository);

        $this->cleanupTables(['rides', 'drivers', 'users']);
    }

    public function testAssignDriverToRequestedRide(): void
    {
        $alice = new Alice();
        $this->addTestUser($alice);
        
        $charlie = new Charlie();
        $this->addTestDriver($charlie);
        
        $ride = new AliceRequestedRide();
        $this->repository->insert($ride);

        $updatedRide = $this->service->assignDriver($ride->id, $charlie->id);

        self::assertNotNull($updatedRide->driverId);
        self::assertEquals($charlie->id, $updatedRide->driverId);
        self::assertSame(State::DriverAccepted, $updatedRide->state);
    }

    public function testAssignDriverToAlreadyAssignedRideThrowsException(): void
    {
        $alice = new Alice();
        $this->addTestUser($alice);
        
        $charlie = new Charlie();
        $this->addTestDriver($charlie);
        
        $david = new David();
        $this->addTestDriver($david);
        
        // Create a ride and assign it to Charlie
        $ride = new Ride(
            id: Id::generate(),
            userId: $alice->id,
            departureLocation: new Downtown(),
            destinationLocation: new Heathrow(),
            driverId: null,
            state: State::Requested,
            createdAt: new \DateTimeImmutable(),
        );
        $this->repository->insert($ride);

        $rideWithCharlie = $this->service->assignDriver($ride->id, $charlie->id);
        
        self::assertEquals($charlie->id, $rideWithCharlie->driverId);

        // Try to assign the same ride to David
        $this->expectException(RideAlreadyAssignedException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Ride %s is already assigned to driver %s, cannot assign to driver %s',
                $ride->id,
                $charlie->id,
                $david->id,
            ),
        );

        $this->service->assignDriver($ride->id, $david->id);
    }

    public function testAssignDriverExceptionContainsCorrectDriverIds(): void
    {
        $alice = new Alice();
        $this->addTestUser($alice);
        
        $charlie = new Charlie();
        $this->addTestDriver($charlie);
        
        $david = new David();
        $this->addTestDriver($david);
        
        $ride = new Ride(
            id: Id::generate(),
            userId: $alice->id,
            departureLocation: new Downtown(),
            destinationLocation: new Heathrow(),
            driverId: null,
            state: State::Requested,
            createdAt: new \DateTimeImmutable(),
        );
        $this->repository->insert($ride);

        $this->service->assignDriver($ride->id, $charlie->id);

        try {
            $this->service->assignDriver($ride->id, $david->id);
            self::fail('Expected RideAlreadyAssignedException to be thrown');
        } catch (RideAlreadyAssignedException $e) {
            self::assertEquals($ride->id, $e->rideId);
            self::assertEquals($charlie->id, $e->assignedDriverId);
            self::assertEquals($david->id, $e->attemptedDriverId);
        }
    }

    public function testAssignDriverToNonExistentRideThrowsException(): void
    {
        $nonExistentRideId = Id::generate();
        $driverId = Id::generate();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Ride not found');

        $this->service->assignDriver($nonExistentRideId, $driverId);
    }
}
