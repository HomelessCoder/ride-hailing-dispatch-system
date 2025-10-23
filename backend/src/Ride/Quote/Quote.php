<?php

declare(strict_types=1);

namespace App\Ride\Quote;

use App\Shared\Distance;
use App\Shared\Duration;
use App\Shared\Id;
use App\Shared\Location\Location;
use App\Shared\Money;

final readonly class Quote
{
    public function __construct(
        public Id $id,
        public Location $departure,
        public Location $destination,
        public Distance $distance,
        public Duration $duration,
        public Money $fare,
    ) {
    }
}
