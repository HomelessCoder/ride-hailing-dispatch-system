<?php

declare(strict_types=1);

namespace App\Ride\Quote;

use App\Shared\Distance;
use App\Shared\Duration;
use App\Shared\Money;

final class QuoteCalculator
{
    public function __construct(
        private readonly FareConfiguration $config,
    ) {
    }

    /**
     * Calculate fare based on the formula:
     * Core Fare = Base Fare + (Distance × Price/km) + (Time × Price/min)
     */
    public function calculate(Distance $distance, Duration $duration): Money
    {
        $distanceCost = $this->config->pricePerKilometer
            ->multiply($distance->toKilometers());

        $durationCost = $this->config->pricePerMinute
            ->multiply($duration->toMinutes());

        return $this->config->baseFare
            ->add($distanceCost)
            ->add($durationCost);
    }
}
