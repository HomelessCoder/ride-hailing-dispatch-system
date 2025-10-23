<?php

declare(strict_types=1);

namespace App\CommandQueue\Infra;

enum Schema: string
{
    case Id = 'id';
    case Status = 'status';
    case Type = 'type';
    case Payload = 'payload';
    case Attempts = 'attempts';
    case LastError = 'last_error';
    case CreatedAt = 'created_at';
    case UpdatedAt = 'updated_at';

    public static function getTableName(): string
    {
        return 'command_queue';
    }
}
