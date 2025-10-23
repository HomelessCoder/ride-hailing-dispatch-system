<?php

declare(strict_types=1);

namespace App\Driver;

use App\Shared\Id;
use App\Shared\Location\Location;
use DateTimeImmutable;

readonly class Driver
{
    public function __construct(
        public Id $id,
        public string $name,
        public string $email,
        public Location $currentLocation,
        public Status $status,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * Create a new Driver instance with updated status
     */
    public function withStatus(Status $status): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            email: $this->email,
            currentLocation: $this->currentLocation,
            status: $status,
            updatedAt: new DateTimeImmutable(),
        );
    }

    /**
     * Create a new Driver instance with updated location
     */
    public function withCurrentLocation(Location $currentLocation): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            email: $this->email,
            currentLocation: $currentLocation,
            status: $this->status,
            updatedAt: new DateTimeImmutable(),
        );
    }

    /**
     * Create a new Driver instance with updated status and location
     */
    public function withStatusAndLocation(Status $status, Location $currentLocation): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            email: $this->email,
            currentLocation: $currentLocation,
            status: $status,
            updatedAt: new DateTimeImmutable(),
        );
    }

    /**
     * Mark driver as available
     */
    public function markAsAvailable(): self
    {
        return $this->withStatus(Status::Available);
    }

    /**
     * Mark driver as busy
     */
    public function markAsBusy(): self
    {
        return $this->withStatus(Status::Busy);
    }

    /**
     * Mark driver as offline
     */
    public function markAsOffline(): self
    {
        return $this->withStatus(Status::Offline);
    }
}
