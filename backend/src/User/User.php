<?php

declare(strict_types=1);

namespace App\User;

use App\Shared\Id;

readonly class User
{
    public function __construct(
        public Id $id,
        public string $name,
        public string $email,
    ) {
    }

    public function withName(string $name): self
    {
        return new self(
            id: $this->id,
            name: $name,
            email: $this->email,
        );
    }
}
