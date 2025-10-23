<?php

declare(strict_types=1);

namespace App\Ride\Queue;

use App\Driver\DriverService;
use App\Driver\Status;
use App\Ride\RideService;
use App\Ride\State;
use App\Shared\EventPublisher;
use App\Shared\ICommandHandler;

/**
 * @implements ICommandHandler<CompleteRideCommand>
 */
final class CompleteRideCommandHandler implements ICommandHandler
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
        if (!$command instanceof CompleteRideCommand) {
            return;
        }

        $ride = $this->rideService->complete($command->rideId);

        $this->driverService->updateStatus($command->driverId, Status::Available);

        $event = new RideCompletedEvent(
            rideId: $command->rideId,
            userId: $ride->userId,
            driverId: $command->driverId,
        );

        $this->eventPublisher->publish("user.{$ride->userId}", $event);
        $this->eventPublisher->publish("driver.{$command->driverId}", $event);
    }
}
