<?php

declare(strict_types=1);

namespace App\Test\Fixture\Ride;

use App\Ride\Ride;
use App\Ride\State;
use App\Shared\Id;
use App\Test\Fixture\Shared\Downtown;
use App\Test\Fixture\Shared\Uptown;

readonly class BobInProgressRide extends Ride
{
    public function __construct()
    {
        parent::__construct(
            id: Id::fromString('019a078d-e95e-757e-8cdb-abe4223ee2a9'),
            userId: Id::fromString('019a078d-e95e-78de-9df5-9b4a39281169'),
            departureLocation: new Downtown(),
            destinationLocation: new Uptown(),
            driverId: null,
            state: State::InProgress,
            createdAt: new \DateTimeImmutable('2025-10-21 10:04:00'),
        );
    }
}
