<?php

declare(strict_types=1);

namespace App\Ride\Queue;

use App\Shared\Id;

final readonly class StartRideCommand
{
    public function __construct(
        public Id $rideId,
        public Id $driverId,
    ) {
    }
}
