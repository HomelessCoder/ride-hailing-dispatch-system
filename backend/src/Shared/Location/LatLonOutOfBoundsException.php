<?php

declare(strict_types=1);

namespace App\Shared\Location;

use Exception;

final class LatLonOutOfBoundsException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function fromLatitude(float $latitude): self
    {
        return new self("Latitude {$latitude} is out of bounds. Must be between -90 and 90 degrees.");
    }

    public static function fromLongitude(float $longitude): self
    {
        return new self("Longitude {$longitude} is out of bounds. Must be between -180 and 180 degrees.");
    }
}
