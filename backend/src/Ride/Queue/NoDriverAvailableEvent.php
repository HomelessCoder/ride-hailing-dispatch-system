<?php

declare(strict_types=1);

namespace App\Ride\Queue;

use App\Shared\Id;

final readonly class NoDriverAvailableEvent
{
    public function __construct(
        public Id $rideId,
        public Id $userId,
        public string $message,
    ) {
    }
}
