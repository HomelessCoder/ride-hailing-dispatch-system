<?php

declare(strict_types=1);

namespace App\Shared;

enum Currency: string
{
    case GBP = 'GBP';
    case USD = 'USD';
    case EUR = 'EUR';
}
