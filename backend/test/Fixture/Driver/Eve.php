<?php

declare(strict_types=1);

namespace App\Test\Fixture\Driver;

use App\Driver\Driver;
use App\Driver\Status;
use App\Shared\Id;
use App\Test\Fixture\Shared\DowntownAlternate;

readonly class Eve extends Driver
{
    public function __construct()
    {
        parent::__construct(
            id: Id::fromString('019a078d-e95e-75c1-ac7e-6121da5520ed'),
            name: 'Eve',
            email: 'eve@taxi.co.uk',
            status: Status::Available,
            currentLocation: new DowntownAlternate(),
            updatedAt: new \DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }
}
