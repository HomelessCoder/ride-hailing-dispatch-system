<?php

declare(strict_types=1);

namespace App\Driver\Queue;

use App\Driver\Status;
use App\Shared\Id;

final readonly class UpdateDriverStatusCommand
{
    public function __construct(
        public Id $driverId,
        public Status $status,
    ) {
    }
}
