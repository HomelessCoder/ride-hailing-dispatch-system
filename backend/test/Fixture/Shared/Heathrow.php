<?php

declare(strict_types=1);

namespace App\Test\Fixture\Shared;

use App\Shared\Location\Location;

readonly class Heathrow extends Location
{
    public function __construct()
    {
        parent::__construct(
            latitude: 51.4700223,
            longitude: -0.4542955,
        );
    }
}
