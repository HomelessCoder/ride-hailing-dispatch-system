<?php

declare(strict_types=1);

namespace App\Ride\Queue;

use App\CommandQueue\Infra\CommandQueueRepository;
use App\Ride\RejectedDriversTracker;
use App\Ride\RideService;
use App\Ride\State;
use App\Shared\EventPublisher;
use App\Shared\ICommandHandler;
use App\Shared\Distance;

/**
 * @implements ICommandHandler<CheckDriverResponseTimeoutCommand>
 */
final readonly class CheckDriverResponseTimeoutCommandHandler implements ICommandHandler
{
    private const MAX_RETRY_ATTEMPTS = 3;

    public function __construct(
        private RideService $rideService,
        private CommandQueueRepository $commandQueueRepository,
        private EventPublisher $eventPublisher,
        private RejectedDriversTracker $rejectedDriversTracker,
    ) {
    }

    public function handle(object $command): void
    {
        // @phpstan-ignore-next-line - runtime check
        if (!$command instanceof CheckDriverResponseTimeoutCommand) {
            return;
        }

        $ride = $this->rideService->get($command->rideId);

        if ($ride === null) {
            // Ride not found - nothing to do
            return;
        }

        // If ride is no longer in dispatching state, driver must have responded
        if ($ride->state !== State::Dispatching) {
            // Driver responded in time - no action needed
            return;
        }

        // Driver didn't respond - timeout occurred
        // Reset ride state back to requested and send a notification to the driver so the UI can hide the request
        $this->rideService->resetToRequested($command->rideId);
        $this->eventPublisher->publish("driver.{$command->driverId}", new DriverRideRequestTimeoutEvent(
            rideId: $command->rideId,
            driverId: $command->driverId,
        ));
        $this->rejectedDriversTracker->recordRejection(
            rideId: $command->rideId,
            driverId: $command->driverId,
        );

        // Try to find another driver, up to MAX_RETRY_ATTEMPTS
        if ($command->attemptNumber < self::MAX_RETRY_ATTEMPTS) {
            $findDriverCommand = new FindDriverCommand(
                rideId: $command->rideId,
                departureLocation: $command->departureLocation,
                attemptNumber: $command->attemptNumber + 1,
                maxDriverDistanceMeters: $command->maxDriverDistanceMeters->add(new Distance(2500)), // Increase search radius with each attempt
            );

            $this->commandQueueRepository->enqueue($findDriverCommand);

            return;
        }

        // Max attempts reached - cancel the ride and notify the user
        $this->rideService->cancel($command->rideId);

        $event = new NoDriverAvailableEvent(
            rideId: $command->rideId,
            userId: $ride->userId,
            message: 'No drivers available at the moment. Please try again later.',
        );

        $this->eventPublisher->publish("user.{$ride->userId}", $event);
    }
}
