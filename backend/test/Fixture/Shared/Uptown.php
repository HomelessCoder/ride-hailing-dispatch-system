<?php

declare(strict_types=1);

namespace App\Test\Fixture\Shared;

use App\Shared\Location\Location;

readonly class Uptown extends Location
{
    public function __construct()
    {
        parent::__construct(
            latitude: 51.515419,
            longitude: -0.141588,
        );
    }
}
