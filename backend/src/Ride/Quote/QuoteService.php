<?php

declare(strict_types=1);

namespace App\Ride\Quote;

use App\Ride\Quote\Infra\DistanceCalculator;
use App\Ride\Quote\Infra\DurationEstimator;
use App\Shared\Id;
use App\Shared\Location\Location;

final class QuoteService
{
    public function __construct(
        private readonly DistanceCalculator $distanceCalculator,
        private readonly DurationEstimator $durationEstimator,
        private readonly QuoteCalculator $quoteCalculator,
    ) {
    }

    /**
     * Create a fare quote for a trip from departure to destination
     */
    public function createQuote(Location $departure, Location $destination): Quote
    {
        $distance = $this->distanceCalculator->calculate($departure, $destination);
        $duration = $this->durationEstimator->estimate($distance);
        $fare = $this->quoteCalculator->calculate($distance, $duration);

        return new Quote(
            id: Id::generate(),
            departure: $departure,
            destination: $destination,
            distance: $distance,
            duration: $duration,
            fare: $fare,
        );
    }
}
