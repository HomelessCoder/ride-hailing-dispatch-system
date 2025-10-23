<?php

declare(strict_types=1);

namespace App\Test\Fixture\Driver;

use App\Driver\Driver;
use App\Driver\Status;
use App\Shared\Id;
use App\Test\Fixture\Shared\Heathrow;

readonly class Charlie extends Driver
{
    public function __construct()
    {
        parent::__construct(
            id: Id::fromString('019a078d-e95e-7981-914e-b5104cd166ee'),
            name: 'Charlie',
            email: 'charlie@taxi.co.uk',
            status: Status::Available,
            currentLocation: new Heathrow(),
            updatedAt: new \DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }
}
