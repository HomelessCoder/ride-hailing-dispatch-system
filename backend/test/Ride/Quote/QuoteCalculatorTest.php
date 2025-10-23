<?php

declare(strict_types=1);

namespace App\Test\Ride\Quote;

use App\Ride\Quote\FareConfiguration;
use App\Ride\Quote\QuoteCalculator;
use App\Shared\Currency;
use App\Shared\Distance;
use App\Shared\Duration;
use App\Shared\Money;
use PHPUnit\Framework\TestCase;

final class QuoteCalculatorTest extends TestCase
{
    private QuoteCalculator $calculator;

    protected function setUp(): void
    {
        // Default configuration: Base £1.50 + £0.80/km + £0.15/min
        $config = FareConfiguration::createDefault();
        $this->calculator = new QuoteCalculator($config);
    }

    public function testCalculateWithExampleValues(): void
    {
        // Example: 2.5 km trip that takes 10 minutes
        // Fare = £1.50 + (2.5 * £0.80) + (10 * £0.15) = £1.50 + £2.00 + £1.50 = £5.00
        $distance = new Distance(2500.0); // 2.5 km
        $duration = new Duration(600); // 10 minutes

        $fare = $this->calculator->calculate($distance, $duration);

        self::assertSame(5.00, $fare->amount);
        self::assertSame(Currency::GBP, $fare->currency);
    }

    public function testCalculateWithZeroDistanceAndDuration(): void
    {
        // Only base fare should apply
        $distance = new Distance(0.0);
        $duration = new Duration(0);

        $fare = $this->calculator->calculate($distance, $duration);

        self::assertSame(1.50, $fare->amount);
        self::assertSame(Currency::GBP, $fare->currency);
    }

    public function testCalculateShortTrip(): void
    {
        // 1 km trip that takes 2 minutes
        // Fare = £1.50 + (1 * £0.80) + (2 * £0.15) = £1.50 + £0.80 + £0.30 = £2.60
        $distance = new Distance(1000.0); // 1 km
        $duration = new Duration(120); // 2 minutes

        $fare = $this->calculator->calculate($distance, $duration);

        self::assertEqualsWithDelta(2.60, $fare->amount, 0.001);
        self::assertSame(Currency::GBP, $fare->currency);
    }

    public function testCalculateLongTrip(): void
    {
        // 10 km trip that takes 30 minutes
        // Fare = £1.50 + (10 * £0.80) + (30 * £0.15) = £1.50 + £8.00 + £4.50 = £14.00
        $distance = new Distance(10000.0); // 10 km
        $duration = new Duration(1800); // 30 minutes

        $fare = $this->calculator->calculate($distance, $duration);

        self::assertSame(14.00, $fare->amount);
        self::assertSame(Currency::GBP, $fare->currency);
    }

    public function testCalculateWithCustomConfiguration(): void
    {
        $config = new FareConfiguration(
            baseFare: new Money(2.00, Currency::USD),
            pricePerKilometer: new Money(1.00, Currency::USD),
            pricePerMinute: new Money(0.20, Currency::USD),
            currency: Currency::USD,
        );
        $calculator = new QuoteCalculator($config);

        // 5 km trip that takes 15 minutes
        // Fare = $2.00 + (5 * $1.00) + (15 * $0.20) = $2.00 + $5.00 + $3.00 = $10.00
        $distance = new Distance(5000.0);
        $duration = new Duration(900);

        $fare = $calculator->calculate($distance, $duration);

        self::assertSame(10.00, $fare->amount);
        self::assertSame(Currency::USD, $fare->currency);
    }

    public function testCalculateWithFractionalValues(): void
    {
        // 3.7 km trip that takes 8.5 minutes
        // Fare = £1.50 + (3.7 * £0.80) + (8.5 * £0.15) = £1.50 + £2.96 + £1.275 = £5.735
        $distance = new Distance(3700.0); // 3.7 km
        $duration = new Duration(510); // 8.5 minutes

        $fare = $this->calculator->calculate($distance, $duration);

        self::assertEqualsWithDelta(5.735, $fare->amount, 0.001);
        self::assertSame(Currency::GBP, $fare->currency);
    }
}
