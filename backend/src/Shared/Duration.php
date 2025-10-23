<?php

declare(strict_types=1);

namespace App\Shared;

use InvalidArgumentException;

final readonly class Duration
{
    public function __construct(
        public int $seconds,
    ) {
        if ($seconds < 0) {
            throw new InvalidArgumentException('Duration cannot be negative');
        }
    }

    public function toMinutes(): float
    {
        return $this->seconds / 60;
    }
}
