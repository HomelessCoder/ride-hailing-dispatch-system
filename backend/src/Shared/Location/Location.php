<?php

declare(strict_types=1);

namespace App\Shared\Location;

readonly class Location
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
        if ($latitude < -90 || $latitude > 90) {
            throw LatLonOutOfBoundsException::fromLatitude($latitude);
        }

        if ($longitude < -180 || $longitude > 180) {
            throw LatLonOutOfBoundsException::fromLongitude($longitude);
        }
    }
}
