<?php

declare(strict_types=1);

namespace App\Ride\Infra;

enum Schema: string
{
    case Id = 'id';
    case UserId = 'user_id';
    case DepartureLocation = 'departure_location';
    case DestinationLocation = 'destination_location';
    case DriverId = 'driver_id';
    case State = 'state';
    case CreatedAt = 'created_at';

    public static function getTableName(): string
    {
        return 'rides';
    }
}
