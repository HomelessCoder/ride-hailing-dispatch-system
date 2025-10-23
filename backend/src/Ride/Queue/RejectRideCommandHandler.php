<?php

declare(strict_types=1);

namespace App\Ride\Queue;

use App\CommandQueue\Infra\CommandQueueRepository;
use App\Ride\RejectedDriversTracker;
use App\Ride\RideService;
use App\Shared\ICommandHandler;

/**
 * @implements ICommandHandler<RejectRideCommand>
 */
final class RejectRideCommandHandler implements ICommandHandler
{
    public function __construct(
        private readonly RideService $rideService,
        private readonly CommandQueueRepository $commandQueueRepository,
        private readonly RejectedDriversTracker $rejectedDriversTracker,
    ) {
    }

    public function handle(object $command): void
    {
        // @phpstan-ignore-next-line - runtime check
        if (!$command instanceof RejectRideCommand) {
            return;
        }

        $ride = $this->rideService->get($command->rideId);
        if ($ride === null) {
            return;
        }

        // Record this driver's rejection so they won't be offered this ride again
        $this->rejectedDriversTracker->recordRejection($command->rideId, $command->driverId);

        $this->rideService->resetToRequested($command->rideId);

        $findDriverCommand = new FindDriverCommand(
            rideId: $command->rideId,
            departureLocation: $ride->departureLocation,
        );

        $this->commandQueueRepository->enqueue($findDriverCommand);
    }
}
