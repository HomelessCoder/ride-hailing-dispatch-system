<?php

declare(strict_types=1);

namespace App\Test\Fixture\Driver;

use App\Driver\Driver;
use App\Driver\Status;
use App\Shared\Id;
use App\Test\Fixture\Shared\Downtown;

readonly class David extends Driver
{
    public function __construct()
    {
        parent::__construct(
            id: Id::fromString('019a078d-e95e-7e9f-8de1-e6d25c4dcbd4'),
            name: 'David',
            email: 'david@taxi.co.uk',
            status: Status::Available,
            currentLocation: new Downtown(),
            updatedAt: new \DateTimeImmutable('2024-01-01 10:00:00'),
        );
    }
}
