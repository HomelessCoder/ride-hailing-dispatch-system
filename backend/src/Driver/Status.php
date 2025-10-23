<?php

declare(strict_types=1);

namespace App\Driver;

enum Status: string
{
    case Available = 'available';
    case Busy = 'busy';
    case Offline = 'offline';
}
