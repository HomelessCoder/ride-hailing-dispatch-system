<?php

declare(strict_types=1);

namespace App\Driver\Queue;

use App\Shared\Id;
use App\Shared\Location\Location;

final readonly class UpdateDriverLocationCommand
{
    public function __construct(
        public Id $driverId,
        public Location $location,
    ) {
    }
}
