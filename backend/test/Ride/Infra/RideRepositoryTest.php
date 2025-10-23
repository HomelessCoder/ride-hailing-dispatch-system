<?php

declare(strict_types=1);

namespace App\Test\Ride\Infra;

use App\Ride\Infra\RideRepository;
use App\Shared\Id;
use App\Test\DatabaseTestCase;
use App\Test\Fixture\Ride\AliceCompletedRide;
use App\Test\User\Infra\UsersFixture;

final class RideRepositoryTest extends DatabaseTestCase
{
    use RidesFixture;
    use UsersFixture;
    private RideRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new RideRepository(self::getPdo());

        $this->cleanupTables(['rides', 'users']);
        $this->addTestUsersSet();
    }

    public function testFindAllReturnsAllRides(): void
    {
        $this->addTestRidesSet();
        $rides = $this->repository->findAll();

        self::assertCount(3, $rides);
    }

    public function testFindReturnsRide(): void
    {
        $this->addTestRidesSet();
        $aliceCompletedRide = new AliceCompletedRide();
        $ride = $this->repository->find($aliceCompletedRide->id);

        self::assertNotNull($ride);
        self::assertEquals($aliceCompletedRide->userId, $ride->userId);
        self::assertEquals($aliceCompletedRide->departureLocation->latitude, $ride->departureLocation->latitude);
        self::assertEquals($aliceCompletedRide->departureLocation->longitude, $ride->departureLocation->longitude);
        self::assertEquals($aliceCompletedRide->destinationLocation->latitude, $ride->destinationLocation->latitude);
        self::assertEquals($aliceCompletedRide->destinationLocation->longitude, $ride->destinationLocation->longitude);
        self::assertEquals($aliceCompletedRide->state, $ride->state);
    }

    public function testFindReturnsNullForNonexistentRide(): void
    {
        $ride = $this->repository->find(Id::generate());

        self::assertNull($ride);
    }
}
