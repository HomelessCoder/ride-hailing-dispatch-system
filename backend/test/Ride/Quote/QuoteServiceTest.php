<?php

declare(strict_types=1);

namespace App\Test\Ride\Quote;

use App\Ride\Quote\FareConfiguration;
use App\Ride\Quote\Infra\DistanceCalculator;
use App\Ride\Quote\Infra\DurationEstimator;
use App\Ride\Quote\QuoteCalculator;
use App\Ride\Quote\QuoteService;
use App\Shared\Currency;
use App\Test\DatabaseTestCase;
use App\Test\Fixture\Shared\Downtown;
use App\Test\Fixture\Shared\Heathrow;
use App\Test\Fixture\Shared\Midtown;
use App\Test\Fixture\Shared\Uptown;

final class QuoteServiceTest extends DatabaseTestCase
{
    private QuoteService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $distanceCalculator = new DistanceCalculator(self::getPdo());
        $durationEstimator = new DurationEstimator();
        $quoteCalculator = new QuoteCalculator(FareConfiguration::createDefault());

        $this->service = new QuoteService(
            $distanceCalculator,
            $durationEstimator,
            $quoteCalculator,
        );
    }

    public function testCreateQuoteForShortTrip(): void
    {
        $departure = new Downtown();
        $destination = new Midtown();

        $quote = $this->service->createQuote($departure, $destination);

        // Verify quote properties
        self::assertInstanceOf(\App\Shared\Id::class, $quote->id);
        self::assertSame($departure, $quote->departure);
        self::assertSame($destination, $quote->destination);

        // Distance should be approximately 800m
        self::assertGreaterThan(0.7, $quote->distance->toKilometers());
        self::assertLessThan(0.9, $quote->distance->toKilometers());

        // Duration at 30 km/h for ~800m should be ~1.6 minutes
        self::assertGreaterThan(1.3, $quote->duration->toMinutes());
        self::assertLessThan(1.9, $quote->duration->toMinutes());

        // Fare = £1.50 + (~0.8 * £0.80) + (~1.6 * £0.15) ≈ £1.50 + £0.64 + £0.24 ≈ £2.38
        self::assertGreaterThan(2.20, $quote->fare->amount);
        self::assertLessThan(2.60, $quote->fare->amount);
        self::assertSame(Currency::GBP, $quote->fare->currency);
    }

    public function testCreateQuoteForMediumTrip(): void
    {
        $departure = new Downtown();
        $destination = new Uptown();

        $quote = $this->service->createQuote($departure, $destination);

        // Distance should be approximately 1.5 km
        self::assertGreaterThan(1.3, $quote->distance->toKilometers());
        self::assertLessThan(1.7, $quote->distance->toKilometers());

        // Duration at 30 km/h for ~1.5 km should be ~3 minutes
        self::assertGreaterThan(2.5, $quote->duration->toMinutes());
        self::assertLessThan(3.5, $quote->duration->toMinutes());

        // Fare should be more than short trip
        self::assertGreaterThan(2.80, $quote->fare->amount);
        self::assertLessThan(3.50, $quote->fare->amount);
        self::assertSame(Currency::GBP, $quote->fare->currency);
    }

    public function testCreateQuoteForLongTrip(): void
    {
        $departure = new Downtown();
        $destination = new Heathrow();

        $quote = $this->service->createQuote($departure, $destination);

        // Distance should be approximately 23 km
        self::assertGreaterThan(22.0, $quote->distance->toKilometers());
        self::assertLessThan(24.0, $quote->distance->toKilometers());

        // Duration at 30 km/h for ~23 km should be ~46 minutes
        self::assertGreaterThan(44.0, $quote->duration->toMinutes());
        self::assertLessThan(48.0, $quote->duration->toMinutes());

        // Fare = £1.50 + (~23 * £0.80) + (~46 * £0.15) ≈ £1.50 + £18.40 + £6.90 ≈ £26.80
        self::assertGreaterThan(25.50, $quote->fare->amount);
        self::assertLessThan(28.50, $quote->fare->amount);
        self::assertSame(Currency::GBP, $quote->fare->currency);
    }

    public function testCreateQuoteWithSameLocation(): void
    {
        $location = new Downtown();

        $quote = $this->service->createQuote($location, $location);

        // Distance should be zero
        self::assertSame(0.0, $quote->distance->meters);

        // Duration should be zero
        self::assertSame(0, $quote->duration->seconds);

        // Fare should be only base fare
        self::assertSame(1.50, $quote->fare->amount);
        self::assertSame(Currency::GBP, $quote->fare->currency);
    }

    public function testCreateQuoteGeneratesUniqueIds(): void
    {
        $departure = new Downtown();
        $destination = new Midtown();

        $quote1 = $this->service->createQuote($departure, $destination);
        $quote2 = $this->service->createQuote($departure, $destination);

        self::assertNotEquals($quote1->id->value, $quote2->id->value);
    }

    public function testCreateQuotePreservesLocations(): void
    {
        $departure = new Downtown();
        $destination = new Midtown();

        $quote = $this->service->createQuote($departure, $destination);

        self::assertSame($departure->latitude, $quote->departure->latitude);
        self::assertSame($departure->longitude, $quote->departure->longitude);
        self::assertSame($destination->latitude, $quote->destination->latitude);
        self::assertSame($destination->longitude, $quote->destination->longitude);
    }
}
