<?php

declare(strict_types=1);

namespace App\Ride\Quote;

use App\Shared\Currency;
use App\Shared\Money;

final readonly class FareConfiguration
{
    public function __construct(
        public Money $baseFare,
        public Money $pricePerKilometer,
        public Money $pricePerMinute,
        public Currency $currency,
    ) {
        // Ensure all money values use the same currency
        if ($baseFare->currency !== $currency
            || $pricePerKilometer->currency !== $currency
            || $pricePerMinute->currency !== $currency
        ) {
            throw new \InvalidArgumentException('All fare components must use the same currency');
        }
    }

    public static function createDefault(): self
    {
        return new self(
            baseFare: new Money(1.50, Currency::GBP),
            pricePerKilometer: new Money(0.80, Currency::GBP),
            pricePerMinute: new Money(0.15, Currency::GBP),
            currency: Currency::GBP,
        );
    }
}
