<?php

declare(strict_types=1);

namespace App\Shared;

use Stringable;
use InvalidArgumentException;
use Ramsey\Uuid\Rfc4122\UuidV7;

final readonly class Id implements Stringable
{
    public function __construct(
        public string $value,
    ) {
        if (UuidV7::isValid($value) === false) {
            throw new InvalidArgumentException('Invalid UUID: ' . $value);
        }
    }

    public static function generate(): self
    {
        return new self((string)UuidV7::uuid7());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
