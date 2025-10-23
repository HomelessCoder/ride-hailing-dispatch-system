<?php

declare(strict_types=1);

namespace App\Test\Fixture\Driver;

use App\Driver\Driver;
use App\Driver\Status;
use App\Shared\Id;
use App\Test\Fixture\Shared\Downtown;

readonly class Frank extends Driver
{
    public function __construct()
    {
        parent::__construct(
            id: Id::fromString('019a078d-e95e-7dba-be51-b1e4d16ff8a8'),
            name: 'Frank',
            email: 'frank@taxi.co.uk',
            status: Status::Offline,
            currentLocation: new Downtown(),
            updatedAt: new \DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }
}
