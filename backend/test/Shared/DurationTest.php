<?php

declare(strict_types=1);

namespace App\Test\Shared;

use App\Shared\Duration;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DurationTest extends TestCase
{
    public function testCreateValidDuration(): void
    {
        $duration = new Duration(600);

        self::assertSame(600, $duration->seconds);
    }

    public function testCannotCreateNegativeDuration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duration cannot be negative');

        new Duration(-60);
    }

    public function testToMinutes(): void
    {
        $duration = new Duration(600);

        self::assertSame(10.0, $duration->toMinutes());
    }

    public function testToMinutesWithZeroDuration(): void
    {
        $duration = new Duration(0);

        self::assertSame(0.0, $duration->toMinutes());
    }

    public function testToMinutesWithFractionalMinutes(): void
    {
        $duration = new Duration(90);

        self::assertSame(1.5, $duration->toMinutes());
    }
}
