<?php

declare(strict_types=1);

namespace App\Test\Fixture\Shared;

use App\Shared\Location\Location;

readonly class DowntownAlternate extends Location
{
    public function __construct()
    {
        parent::__construct(
            latitude: 51.5078509,
            longitude: -0.1297583,
        );
    }
}
