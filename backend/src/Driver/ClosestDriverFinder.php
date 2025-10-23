<?php

declare(strict_types=1);

namespace App\Driver;

use App\Driver\Infra\DriverRepository;
use App\Shared\Distance;
use App\Shared\Id;
use App\Shared\Location\Location;

final class ClosestDriverFinder
{
    public function __construct(
        private readonly DriverRepository $repository,
    ) {
    }

    /**
     * @param Id[] $excludeDriverIds
     * @param Distance|null $maxDistance Maximum acceptable distance. If null, no distance limit is applied.
     */
    public function findClosestAvailableDriver(
        Location $location,
        array $excludeDriverIds = [],
        ?Distance $maxDistance = null,
    ): ?Driver {
        return $this->repository->findClosestByStatusAndLocation(
            status: Status::Available,
            location: $location,
            excludeDriverIds: $excludeDriverIds,
            maxDistance: $maxDistance,
        );
    }
}
