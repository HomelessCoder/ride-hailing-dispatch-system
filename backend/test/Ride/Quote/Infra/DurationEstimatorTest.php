<?php

declare(strict_types=1);

namespace App\Test\Ride\Quote\Infra;

use App\Ride\Quote\Infra\DurationEstimator;
use App\Shared\Distance;
use PHPUnit\Framework\TestCase;

final class DurationEstimatorTest extends TestCase
{
    private DurationEstimator $estimator;

    protected function setUp(): void
    {
        $this->estimator = new DurationEstimator();
    }

    public function testEstimateDurationFor30KmAtAverageSpeed(): void
    {
        // At 30 km/h, 30 km should take 1 hour (3600 seconds)
        $distance = new Distance(30000.0); // 30 km

        $duration = $this->estimator->estimate($distance);

        self::assertSame(3600, $duration->seconds);
    }

    public function testEstimateDurationFor15Km(): void
    {
        // At 30 km/h, 15 km should take 30 minutes (1800 seconds)
        $distance = new Distance(15000.0); // 15 km

        $duration = $this->estimator->estimate($distance);

        self::assertSame(1800, $duration->seconds);
    }

    public function testEstimateDurationForShortDistance(): void
    {
        // At 30 km/h, 2.5 km should take 5 minutes (300 seconds)
        $distance = new Distance(2500.0); // 2.5 km

        $duration = $this->estimator->estimate($distance);

        self::assertSame(300, $duration->seconds);
    }

    public function testEstimateDurationForZeroDistance(): void
    {
        $distance = new Distance(0.0);

        $duration = $this->estimator->estimate($distance);

        self::assertSame(0, $duration->seconds);
    }

    public function testEstimateDurationFor1Km(): void
    {
        // At 30 km/h, 1 km should take 2 minutes (120 seconds)
        $distance = new Distance(1000.0); // 1 km

        $duration = $this->estimator->estimate($distance);

        self::assertSame(120, $duration->seconds);
    }

    public function testEstimateDurationRoundsToNearestSecond(): void
    {
        // At 30 km/h, 2.7 km should take 5.4 minutes (324 seconds)
        $distance = new Distance(2700.0); // 2.7 km

        $duration = $this->estimator->estimate($distance);

        self::assertSame(324, $duration->seconds);
    }
}
