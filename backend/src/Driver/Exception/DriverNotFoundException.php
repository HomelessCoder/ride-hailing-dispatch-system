<?php

declare(strict_types=1);

namespace App\Driver\Exception;

use App\Shared\Id;

class DriverNotFoundException extends \DomainException
{
    public static function withId(Id $driverId): self
    {
        return new self("Driver with ID {$driverId} not found");
    }
}
