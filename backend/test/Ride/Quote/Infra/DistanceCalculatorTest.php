<?php

declare(strict_types=1);

namespace App\Test\Ride\Quote\Infra;

use App\Ride\Quote\Infra\DistanceCalculator;
use App\Test\DatabaseTestCase;
use App\Test\Fixture\Shared\Downtown;
use App\Test\Fixture\Shared\DowntownAlternate;
use App\Test\Fixture\Shared\Heathrow;
use App\Test\Fixture\Shared\Midtown;
use App\Test\Fixture\Shared\Uptown;

final class DistanceCalculatorTest extends DatabaseTestCase
{
    private DistanceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new DistanceCalculator(self::getPdo());
    }

    public function testCalculateDistanceBetweenTwoPoints(): void
    {
        $downtown = new Downtown();
        $midtown = new Midtown();

        $distance = $this->calculator->calculate($downtown, $midtown);

        // Distance should be approximately 800m
        self::assertGreaterThan(700.0, $distance->meters);
        self::assertLessThan(900.0, $distance->meters);
    }

    public function testCalculateDistanceBetweenSamePoint(): void
    {
        $location = new Downtown();

        $distance = $this->calculator->calculate($location, $location);

        self::assertSame(0.0, $distance->meters);
    }

    public function testCalculateDistanceBetweenCloseLocations(): void
    {
        $downtown = new Downtown();
        $downtownAlt = new DowntownAlternate();

        $distance = $this->calculator->calculate($downtown, $downtownAlt);

        // Distance should be very small (< 200m)
        self::assertGreaterThan(0.0, $distance->meters);
        self::assertLessThan(200.0, $distance->meters);
    }

    public function testCalculateDistanceToHeathrow(): void
    {
        $downtown = new Downtown();
        $heathrow = new Heathrow();

        $distance = $this->calculator->calculate($downtown, $heathrow);

        // Distance should be approximately 23 km
        self::assertGreaterThan(22000.0, $distance->meters);
        self::assertLessThan(24000.0, $distance->meters);
    }

    public function testCalculateDistanceDowntownToUptown(): void
    {
        $downtown = new Downtown();
        $uptown = new Uptown();

        $distance = $this->calculator->calculate($downtown, $uptown);

        // Distance should be approximately 1.5 km
        self::assertGreaterThan(1300.0, $distance->meters);
        self::assertLessThan(1700.0, $distance->meters);
    }

    public function testCalculateDistanceIsSymmetric(): void
    {
        $downtown = new Downtown();
        $midtown = new Midtown();

        $distanceAB = $this->calculator->calculate($downtown, $midtown);
        $distanceBA = $this->calculator->calculate($midtown, $downtown);

        self::assertSame($distanceAB->meters, $distanceBA->meters);
    }
}
