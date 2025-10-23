<?php

declare(strict_types=1);

namespace App\Test\Fixture\Shared;

use App\Shared\Location\Location;

readonly class Midtown extends Location
{
    public function __construct()
    {
        parent::__construct(
            latitude: 51.5125,
            longitude: -0.1357,
        );
    }
}
