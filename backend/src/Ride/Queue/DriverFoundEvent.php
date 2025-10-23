<?php

declare(strict_types=1);

namespace App\Ride\Queue;

use App\Shared\Id;
use App\Shared\Location\Location;

final readonly class DriverFoundEvent
{
    public function __construct(
        public Id $rideId,
        public Id $driverId,
        public string $driverName,
        public Location $departureLocation,
        public Location $destinationLocation,
    ) {
    }
}
