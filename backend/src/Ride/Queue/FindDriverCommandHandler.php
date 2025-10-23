<?php

declare(strict_types=1);

namespace App\Ride\Queue;

use App\CommandQueue\Infra\CommandQueueRepository;
use App\Driver\ClosestDriverFinder;
use App\Ride\RejectedDriversTracker;
use App\Ride\RideService;
use App\Shared\Distance;
use App\Shared\EventPublisher;
use App\Shared\ICommandHandler;

/**
 * @implements ICommandHandler<FindDriverCommand>
 */
final class FindDriverCommandHandler implements ICommandHandler
{
    private const DRIVER_RESPONSE_TIMEOUT_SECONDS = 30;
    private const MAX_RETRY_ATTEMPTS = 3;
    private const MAX_DISTANCE_METERS = 15000;

    public function __construct(
        private readonly RideService $rideService,
        private readonly ClosestDriverFinder $driverFinder,
        private readonly EventPublisher $eventPublisher,
        private readonly CommandQueueRepository $commandQueueRepository,
        private readonly RejectedDriversTracker $rejectedDriversTracker,
    ) {
    }

    public function handle(object $command): void
    {
        // @phpstan-ignore-next-line - runtime check
        if (!$command instanceof FindDriverCommand) {
            return;
        }

        $ride = $this->rideService->get($command->rideId);

        if ($ride === null) {
            throw new \RuntimeException('Ride not found');
        }

        $this->rideService->markAsDispatching($command->rideId);

        // Get list of drivers who have already rejected this ride
        $excludedDriverIds = $this->rejectedDriversTracker->getRejectedDriverIds($command->rideId);

        $driver = $this->driverFinder->findClosestAvailableDriver(
            location: $command->departureLocation,
            excludeDriverIds: $excludedDriverIds,
            maxDistance: $command->maxDriverDistanceMeters,
        );

        if ($driver === null) {
            // Check if we have exhausted all retry attempts or reached a maximum search radius
            if ($command->attemptNumber >= self::MAX_RETRY_ATTEMPTS ||
                $command->maxDriverDistanceMeters->meters >= self::MAX_DISTANCE_METERS) {
                // Max attempts reached - cancel the ride and notify the user
                $this->rideService->cancel($command->rideId);

                $event = new NoDriverAvailableEvent(
                    rideId: $command->rideId,
                    userId: $ride->userId,
                    message: 'No drivers available at the moment. Please try again later.',
                );

                $this->eventPublisher->publish("user.{$ride->userId}", $event);

                return;
            }
            // No driver found - re-enqueue the command with increased attempt number and expanded search radius
            $findDriverCommand = new FindDriverCommand(
                rideId: $command->rideId,
                departureLocation: $command->departureLocation,
                attemptNumber: $command->attemptNumber + 1,
                maxDriverDistanceMeters: $command->maxDriverDistanceMeters->add(new Distance(2500)),
            );
            $this->commandQueueRepository->enqueue($findDriverCommand);

            return;
        }

        $event = new DriverFoundEvent(
            rideId: $command->rideId,
            driverId: $driver->id,
            driverName: $driver->name,
            departureLocation: $ride->departureLocation,
            destinationLocation: $ride->destinationLocation,
        );

        $this->eventPublisher->publish("driver.{$driver->id}", $event);

        // Enqueue a timeout check command to handle cases where driver doesn't respond
        $timeoutCommand = new CheckDriverResponseTimeoutCommand(
            rideId: $command->rideId,
            driverId: $driver->id,
            departureLocation: $command->departureLocation,
            attemptNumber: $command->attemptNumber,
        );

        $this->commandQueueRepository->enqueueDelayed(
            $timeoutCommand,
            self::DRIVER_RESPONSE_TIMEOUT_SECONDS,
        );
    }
}
