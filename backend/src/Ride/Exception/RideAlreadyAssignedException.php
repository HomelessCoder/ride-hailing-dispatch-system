<?php

declare(strict_types=1);

namespace App\Ride\Exception;

use App\Shared\Id;

final class RideAlreadyAssignedException extends \RuntimeException
{
    public function __construct(
        public readonly Id $rideId,
        public readonly Id $assignedDriverId,
        public readonly Id $attemptedDriverId,
    ) {
        parent::__construct(
            sprintf(
                'Ride %s is already assigned to driver %s, cannot assign to driver %s',
                $rideId,
                $assignedDriverId,
                $attemptedDriverId,
            ),
        );
    }
}
