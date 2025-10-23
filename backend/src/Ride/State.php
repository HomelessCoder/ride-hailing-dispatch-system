<?php

declare(strict_types=1);

namespace App\Ride;

enum State: string
{
    /** Created by user, no driver assigned yet */
    case Requested = 'requested';

    /** Worker is actively trying to assign a driver */
    case Dispatching = 'dispatching';

    /** Driver confirmed the request (moving towards pickup) */
    case DriverAccepted = 'driver_accepted';

    /** Pickup complete, ride started */
    case InProgress = 'in_progress';

    /** Ride completed successfully */
    case Completed = 'completed';

    /** Ride was cancelled */
    case Cancelled = 'cancelled';
}
