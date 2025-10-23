<?php

declare(strict_types=1);

namespace App\Ride\Queue;

use App\CommandQueue\Infra\CommandQueueRepository;
use App\Ride\Ride;
use App\Ride\RideService;
use App\Ride\State;
use App\Shared\ICommandHandler;
use DateTimeImmutable;

/**
 * @implements ICommandHandler<RequestRide>
 */
final class RequestRideCommandHandler implements ICommandHandler
{
    public function __construct(
        private RideService $rideService,
        private CommandQueueRepository $commandQueueRepository,
    ) {
    }

    public function handle(object $command): void
    {
        // @phpstan-ignore-next-line - runtime check
        if (!$command instanceof RequestRide) {
            return;
        }

        $ride = new Ride(
            id: $command->rideId,
            userId: $command->userId,
            departureLocation: $command->departureLocation,
            destinationLocation: $command->destinationLocation,
            driverId: null,
            state: State::Requested,
            createdAt: new DateTimeImmutable(),
        );

        $ride = $this->rideService->create($ride);

        $findDriverCommand = new FindDriverCommand(
            rideId: $ride->id,
            departureLocation: $ride->departureLocation,
        );

        $this->commandQueueRepository->enqueue($findDriverCommand);
    }
}
