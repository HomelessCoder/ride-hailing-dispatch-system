<?php

declare(strict_types=1);

namespace App\Ride;

use App\Shared\Id;
use App\Shared\Location\Location;
use DateTimeImmutable;

readonly class Ride
{
    public function __construct(
        public Id $id,
        public Id $userId,
        public Location $departureLocation,
        public Location $destinationLocation,
        public ?Id $driverId,
        public State $state,
        public DateTimeImmutable $createdAt,
    ) {
    }

    public function withState(State $state): self
    {
        return new self(
            id: $this->id,
            userId: $this->userId,
            departureLocation: $this->departureLocation,
            destinationLocation: $this->destinationLocation,
            driverId: $this->driverId,
            state: $state,
            createdAt: $this->createdAt,
        );
    }

    public function withDriver(Id $driverId, State $state): self
    {
        return new self(
            id: $this->id,
            userId: $this->userId,
            departureLocation: $this->departureLocation,
            destinationLocation: $this->destinationLocation,
            driverId: $driverId,
            state: $state,
            createdAt: $this->createdAt,
        );
    }
}
