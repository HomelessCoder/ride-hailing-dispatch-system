<?php

declare(strict_types=1);

namespace App\Test\Fixture\User;

use App\Shared\Id;
use App\User\User;

readonly class Alice extends User
{
    public function __construct()
    {
        parent::__construct(
            id: Id::fromString('019a078d-e95e-7606-a2d8-b3dfa4bc1934'),
            name: 'Alice',
            email: 'alice@example.com',
        );
    }
}
