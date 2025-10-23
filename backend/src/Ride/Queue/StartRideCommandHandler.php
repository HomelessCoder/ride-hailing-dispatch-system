<?php

declare(strict_types=1);

namespace App\Ride\Queue;

use App\Ride\RideService;
use App\Ride\State;
use App\Shared\EventPublisher;
use App\Shared\ICommandHandler;

/**
 * @implements ICommandHandler<StartRideCommand>
 */
final class StartRideCommandHandler implements ICommandHandler
{
    public function __construct(
        private readonly RideService $rideService,
        private readonly EventPublisher $eventPublisher,
    ) {
    }

    public function handle(object $command): void
    {
        // @phpstan-ignore-next-line - runtime check
        if (!$command instanceof StartRideCommand) {
            return;
        }

        $ride = $this->rideService->startRide($command->rideId);

        $event = new RideStartedEvent(
            rideId: $command->rideId,
            userId: $ride->userId,
            driverId: $command->driverId,
        );

        $this->eventPublisher->publish("user.{$ride->userId}", $event);
        $this->eventPublisher->publish("driver.{$command->driverId}", $event);
    }
}
