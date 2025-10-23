<?php

declare(strict_types=1);

namespace App\Ride;

use App\Ride\Exception\RideAlreadyAssignedException;
use App\Ride\Infra\RideRepository;
use App\Shared\Id;

class RideService
{
    public function __construct(
        private readonly RideRepository $repository,
    ) {
    }

    public function create(Ride $ride): Ride
    {
        $this->repository->beginTransaction();

        try {
            $inserted = $this->repository->insert($ride);

            if ($inserted === false) {
                throw new \RuntimeException('Failed to create ride');
            }

            $this->repository->commit();

            return $ride;
        } catch (\Exception $e) {
            $this->repository->rollback();

            throw $e;
        }
    }

    public function markAsDispatching(Id $rideId): Ride
    {
        return $this->updateState($rideId, State::Dispatching);
    }

    public function assignDriver(Id $rideId, Id $driverId): Ride
    {
        $this->repository->beginTransaction();

        try {
            $ride = $this->repository->find($rideId, forUpdate: true);

            if ($ride === null) {
                throw new \RuntimeException('Ride not found');
            }

            // Check if ride already has a driver assigned
            if ($ride->driverId !== null) {
                throw new RideAlreadyAssignedException(
                    rideId: $rideId,
                    assignedDriverId: $ride->driverId,
                    attemptedDriverId: $driverId,
                );
            }

            $updatedRide = $ride->withDriver($driverId, State::DriverAccepted);

            $this->repository->update($updatedRide);
            $this->repository->commit();

            return $updatedRide;
        } catch (\Exception $e) {
            $this->repository->rollback();

            throw $e;
        }
    }

    public function resetToRequested(Id $rideId): Ride
    {
        return $this->updateState($rideId, State::Requested);
    }

    public function startRide(Id $rideId): Ride
    {
        return $this->updateState($rideId, State::InProgress);
    }

    public function cancel(Id $rideId): Ride
    {
        return $this->updateState($rideId, State::Cancelled);
    }

    public function complete(Id $rideId): Ride
    {
        return $this->updateState($rideId, State::Completed);
    }

    public function get(Id $rideId): ?Ride
    {
        return $this->repository->find($rideId);
    }

    private function updateState(Id $rideId, State $state): Ride
    {
        $this->repository->beginTransaction();

        try {
            $ride = $this->repository->find($rideId, forUpdate: true);

            if ($ride === null) {
                throw new \RuntimeException('Ride not found');
            }

            $updatedRide = $ride->withState($state);

            $this->repository->update($updatedRide);
            $this->repository->commit();

            return $updatedRide;
        } catch (\Exception $e) {
            $this->repository->rollback();

            throw $e;
        }
    }
}
