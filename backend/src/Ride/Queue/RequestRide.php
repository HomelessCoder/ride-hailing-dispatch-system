<?php

declare(strict_types=1);

namespace App\Ride\Queue;

use App\Shared\Id;
use App\Shared\Location\Location;

final readonly class RequestRide
{
    public function __construct(
        public Id $rideId,
        public Id $userId,
        public Location $departureLocation,
        public Location $destinationLocation,
    ) {
    }
}
