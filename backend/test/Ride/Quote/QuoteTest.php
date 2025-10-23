<?php

declare(strict_types=1);

namespace App\Test\Ride\Quote;

use App\Ride\Quote\Quote;
use App\Shared\Currency;
use App\Shared\Distance;
use App\Shared\Duration;
use App\Shared\Id;
use App\Shared\Location\Location;
use App\Shared\Money;
use PHPUnit\Framework\TestCase;

final class QuoteTest extends TestCase
{
    public function testCreateQuote(): void
    {
        $id = Id::generate();
        $departure = new Location(51.5074, -0.1278);
        $destination = new Location(51.5155, -0.0922);
        $distance = new Distance(2500.0);
        $duration = new Duration(600);
        $fare = new Money(5.00, Currency::GBP);

        $quote = new Quote(
            id: $id,
            departure: $departure,
            destination: $destination,
            distance: $distance,
            duration: $duration,
            fare: $fare,
        );

        self::assertSame($id, $quote->id);
        self::assertSame($departure, $quote->departure);
        self::assertSame($destination, $quote->destination);
        self::assertSame($distance, $quote->distance);
        self::assertSame($duration, $quote->duration);
        self::assertSame($fare, $quote->fare);
    }

    public function testQuoteIsReadonly(): void
    {
        $quote = new Quote(
            id: Id::generate(),
            departure: new Location(51.5074, -0.1278),
            destination: new Location(51.5155, -0.0922),
            distance: new Distance(2500.0),
            duration: new Duration(600),
            fare: new Money(5.00, Currency::GBP),
        );

        // Verify readonly properties exist
        self::assertInstanceOf(Id::class, $quote->id);
        self::assertInstanceOf(Location::class, $quote->departure);
        self::assertInstanceOf(Location::class, $quote->destination);
        self::assertInstanceOf(Distance::class, $quote->distance);
        self::assertInstanceOf(Duration::class, $quote->duration);
        self::assertInstanceOf(Money::class, $quote->fare);
    }
}
