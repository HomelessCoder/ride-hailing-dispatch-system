<?php

declare(strict_types=1);

namespace App\Test\Fixture\User;

use App\Shared\Id;
use App\User\User;

readonly class Bob extends User
{
    public function __construct()
    {
        parent::__construct(
            id: Id::fromString('019a078d-e95e-78de-9df5-9b4a39281169'),
            name: 'Bob',
            email: 'bob@example.com',
        );
    }
}
