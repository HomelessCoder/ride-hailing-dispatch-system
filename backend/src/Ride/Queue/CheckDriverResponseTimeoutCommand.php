<?php

declare(strict_types=1);

namespace App\Ride\Queue;

use App\Shared\Id;
use App\Shared\Location\Location;
use App\Shared\Distance;

final readonly class CheckDriverResponseTimeoutCommand
{
    public function __construct(
        public Id $rideId,
        public Id $driverId,
        public Location $departureLocation,
        public int $attemptNumber = 1,
        public Distance $maxDriverDistanceMeters = new Distance(5000),
    ) {
    }
}
