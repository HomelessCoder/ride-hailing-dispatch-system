<?php

declare(strict_types=1);

namespace App\User\Infra;

enum Schema: string
{
    case Id = 'id';
    case Name = 'name';
    case Email = 'email';

    public static function getTableName(): string
    {
        return 'users';
    }
}
