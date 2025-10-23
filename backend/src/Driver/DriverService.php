<?php

declare(strict_types=1);

namespace App\Driver;

use App\Driver\Exception\DriverNotFoundException;
use App\Driver\Infra\DriverRepository;
use App\Shared\Id;
use App\Shared\Location\Location;

class DriverService
{
    public function __construct(
        private readonly DriverRepository $repository,
    ) {
    }

    /**
     * Update driver status
     */
    public function updateStatus(Id $driverId, Status $newStatus): Driver
    {
        $this->repository->beginTransaction();

        try {
            $driver = $this->repository->find($driverId, forUpdate: true);

            if ($driver === null) {
                throw DriverNotFoundException::withId($driverId);
            }

            $updatedDriver = $driver->withStatus($newStatus);

            $this->repository->update($updatedDriver);
            $this->repository->commit();

            return $updatedDriver;
        } catch (\Exception $e) {
            $this->repository->rollback();

            throw $e;
        }
    }

    /**
     * Update driver location
     */
    public function updateLocation(Id $driverId, Location $newLocation): Driver
    {
        $this->repository->beginTransaction();

        try {
            $driver = $this->repository->find($driverId, forUpdate: true);

            if ($driver === null) {
                throw DriverNotFoundException::withId($driverId);
            }

            $updatedDriver = $driver->withCurrentLocation($newLocation);

            $this->repository->update($updatedDriver);
            $this->repository->commit();

            return $updatedDriver;
        } catch (\Exception $e) {
            $this->repository->rollback();

            throw $e;
        }
    }

    /**
     * Update driver status and location together
     */
    public function updateStatusAndLocation(Id $driverId, Status $newStatus, Location $newLocation): Driver
    {
        $this->repository->beginTransaction();

        try {
            $driver = $this->repository->find($driverId, forUpdate: true);

            if ($driver === null) {
                throw DriverNotFoundException::withId($driverId);
            }

            $updatedDriver = $driver->withStatusAndLocation($newStatus, $newLocation);

            $this->repository->update($updatedDriver);
            $this->repository->commit();

            return $updatedDriver;
        } catch (\Exception $e) {
            $this->repository->rollback();

            throw $e;
        }
    }

    /**
     * Set driver as available
     */
    public function setAvailable(Id $driverId): Driver
    {
        $this->repository->beginTransaction();

        try {
            $driver = $this->repository->find($driverId, forUpdate: true);

            if ($driver === null) {
                throw DriverNotFoundException::withId($driverId);
            }

            $updatedDriver = $driver->markAsAvailable();

            $this->repository->update($updatedDriver);
            $this->repository->commit();

            return $updatedDriver;
        } catch (\Exception $e) {
            $this->repository->rollback();

            throw $e;
        }
    }

    /**
     * Set driver as busy
     */
    public function setBusy(Id $driverId): Driver
    {
        $this->repository->beginTransaction();

        try {
            $driver = $this->repository->find($driverId, forUpdate: true);

            if ($driver === null) {
                throw DriverNotFoundException::withId($driverId);
            }

            $updatedDriver = $driver->markAsBusy();

            $this->repository->update($updatedDriver);
            $this->repository->commit();

            return $updatedDriver;
        } catch (\Exception $e) {
            $this->repository->rollback();

            throw $e;
        }
    }

    /**
     * Set driver as offline
     */
    public function setOffline(Id $driverId): Driver
    {
        $this->repository->beginTransaction();

        try {
            $driver = $this->repository->find($driverId, forUpdate: true);

            if ($driver === null) {
                throw DriverNotFoundException::withId($driverId);
            }

            $updatedDriver = $driver->markAsOffline();

            $this->repository->update($updatedDriver);
            $this->repository->commit();

            return $updatedDriver;
        } catch (\Exception $e) {
            $this->repository->rollback();

            throw $e;
        }
    }

    /**
     * Get driver by ID
     */
    public function getDriver(Id $driverId): ?Driver
    {
        return $this->repository->find($driverId);
    }
}
