<?php

declare(strict_types=1);

namespace App\Shared;

use InvalidArgumentException;

final readonly class Distance
{
    public function __construct(
        public float $meters,
    ) {
        if ($meters < 0) {
            throw new InvalidArgumentException('Distance cannot be negative');
        }
    }

    public function toKilometers(): float
    {
        return $this->meters / 1000;
    }

    public function add(Distance $other): Distance
    {
        return new Distance($this->meters + $other->meters);
    }
}
