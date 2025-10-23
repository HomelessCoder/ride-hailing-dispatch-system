<?php

declare(strict_types=1);

namespace App\Ride\Queue;

use App\Driver\DriverService;
use App\Driver\Status;
use App\Ride\Exception\RideAlreadyAssignedException;
use App\Ride\RideService;
use App\Shared\EventPublisher;
use App\Shared\ICommandHandler;

/**
 * @implements ICommandHandler<AcceptRideCommand>
 */
final class AcceptRideCommandHandler implements ICommandHandler
{
    public function __construct(
        private readonly RideService $rideService,
        private readonly DriverService $driverService,
        private readonly EventPublisher $eventPublisher,
    ) {
    }

    public function handle(object $command): void
    {
        // @phpstan-ignore-next-line - runtime check
        if (!$command instanceof AcceptRideCommand) {
            return;
        }

        try {
            $ride = $this->rideService->assignDriver($command->rideId, $command->driverId);

            $this->driverService->updateStatus($command->driverId, Status::Busy);

            $event = new RideAcceptedEvent(
                rideId: $command->rideId,
                userId: $ride->userId,
                driverId: $command->driverId,
            );

            $this->eventPublisher->publish("user.{$ride->userId}", $event);
        } catch (RideAlreadyAssignedException $e) {
            // Ride was already assigned to another driver, silently ignore
            // The driver remains available for other rides
            return;
        }
    }
}
