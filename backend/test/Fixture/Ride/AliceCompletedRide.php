<?php

declare(strict_types=1);

namespace App\Test\Fixture\Ride;

use App\Ride\Ride;
use App\Ride\State;
use App\Shared\Id;
use App\Test\Fixture\Shared\Downtown;
use App\Test\Fixture\Shared\Heathrow;

readonly class AliceCompletedRide extends Ride
{
    public function __construct()
    {
        parent::__construct(
            id: Id::fromString('019a078d-e95e-70b6-8b6d-d3b80bf115cf'),
            userId: Id::fromString('019a078d-e95e-7606-a2d8-b3dfa4bc1934'),
            departureLocation: new Heathrow(),
            destinationLocation: new Downtown(),
            driverId: null,
            state: State::Completed,
            createdAt: new \DateTimeImmutable('2025-10-21 10:00:00'),
        );
    }
}
