<?php

declare(strict_types=1);

namespace App\Test\Fixture\Ride;

use App\Ride\Ride;
use App\Ride\State;
use App\Shared\Id;
use App\Test\Fixture\Shared\Downtown;
use App\Test\Fixture\Shared\Heathrow;

readonly class AliceRequestedRide extends Ride
{
    public function __construct()
    {
        parent::__construct(
            id: Id::fromString('019a078d-e95e-7752-8d80-748cb0a901e9'),
            userId: Id::fromString('019a078d-e95e-7606-a2d8-b3dfa4bc1934'),
            departureLocation: new Downtown(),
            destinationLocation: new Heathrow(),
            driverId: null,
            state: State::Requested,
            createdAt: new \DateTimeImmutable('2025-10-21 10:35:00'),
        );
    }
}
