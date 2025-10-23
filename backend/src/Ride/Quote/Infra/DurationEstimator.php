<?php

declare(strict_types=1);

namespace App\Ride\Quote\Infra;

use App\Shared\Distance;
use App\Shared\Duration;

final class DurationEstimator
{
    private const AVERAGE_SPEED_KMH = 30.0;

    /**
     * Estimate trip duration based on distance and average speed
     * Simple simulation: duration = distance / average_speed
     */
    public function estimate(Distance $distance): Duration
    {
        $hours = $distance->toKilometers() / self::AVERAGE_SPEED_KMH;
        $seconds = (int)round($hours * 3600);

        return new Duration($seconds);
    }
}
