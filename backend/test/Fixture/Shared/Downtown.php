<?php

declare(strict_types=1);

namespace App\Test\Fixture\Shared;

use App\Shared\Location\Location;

readonly class Downtown extends Location
{
    public function __construct()
    {
        parent::__construct(
            latitude: 51.5073509,
            longitude: -0.1277583,
        );
    }
}
