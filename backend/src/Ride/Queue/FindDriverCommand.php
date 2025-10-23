<?php

declare(strict_types=1);

namespace App\Ride\Queue;

use App\Shared\Distance;
use App\Shared\Id;
use App\Shared\Location\Location;

final readonly class FindDriverCommand
{
    public function __construct(
        public Id $rideId,
        public Location $departureLocation,
        public int $attemptNumber = 1,
        public Distance $maxDriverDistanceMeters = new Distance(5000),
    ) {
    }
}
