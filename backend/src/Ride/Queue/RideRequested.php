<?php

declare(strict_types=1);

namespace App\Ride\Queue;

use App\Ride\Ride;

final readonly class RideRequested
{
    public function __construct(
        public Ride $ride,
    ) {
    }
}
