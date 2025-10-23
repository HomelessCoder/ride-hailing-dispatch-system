<?php

declare(strict_types=1);

namespace App\Driver\Infra;

enum Schema: string
{
    case Id = 'id';
    case Name = 'name';
    case Email = 'email';
    case CurrentLocation = 'current_location';
    case Status = 'status';
    case UpdatedAt = 'updated_at';

    public static function getTableName(): string
    {
        return 'drivers';
    }
}
