<?php

declare(strict_types=1);

namespace App\Driver\Queue;

use App\Driver\DriverService;
use App\Shared\ICommandHandler;

/**
 * @implements ICommandHandler<UpdateDriverLocationCommand>
 */
final class UpdateDriverLocationCommandHandler implements ICommandHandler
{
    public function __construct(
        private readonly DriverService $driverService,
    ) {
    }

    public function handle(object $command): void
    {
        // @phpstan-ignore-next-line - runtime check
        if (!$command instanceof UpdateDriverLocationCommand) {
            return;
        }

        $this->driverService->updateLocation($command->driverId, $command->location);
    }
}
