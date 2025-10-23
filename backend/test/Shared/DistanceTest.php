<?php

declare(strict_types=1);

namespace App\Test\Shared;

use App\Shared\Distance;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DistanceTest extends TestCase
{
    public function testCreateValidDistance(): void
    {
        $distance = new Distance(1000.0);

        self::assertSame(1000.0, $distance->meters);
    }

    public function testCannotCreateNegativeDistance(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Distance cannot be negative');

        new Distance(-100.0);
    }

    public function testToKilometers(): void
    {
        $distance = new Distance(2500.0);

        self::assertSame(2.5, $distance->toKilometers());
    }

    public function testToKilometersWithZeroDistance(): void
    {
        $distance = new Distance(0.0);

        self::assertSame(0.0, $distance->toKilometers());
    }

    public function testToKilometersWithSmallDistance(): void
    {
        $distance = new Distance(500.0);

        self::assertSame(0.5, $distance->toKilometers());
    }
}
