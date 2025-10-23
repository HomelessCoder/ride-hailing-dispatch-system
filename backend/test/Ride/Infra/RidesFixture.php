<?php

declare(strict_types=1);

namespace App\Test\Ride\Infra;

use App\Ride\Ride;
use App\Ride\State;
use App\Shared\Id;
use App\Shared\Location\Location;
use App\Test\Fixture\Ride\AliceCompletedRide;
use App\Test\Fixture\Ride\AliceRequestedRide;
use App\Test\Fixture\Ride\BobInProgressRide;
use DateTimeImmutable;

trait RidesFixture
{
    protected function addTestRidesSet(): void
    {
        $this->addTestRide(new AliceCompletedRide());
        $this->addTestRide(new BobInProgressRide());
        $this->addTestRide(new AliceRequestedRide());
    }

    protected function addTestRide(
        Ride $ride,
    ): void {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare(
            "INSERT INTO rides (id, user_id, departure_location, destination_location, state, created_at) 
             VALUES (:id, :user_id, ST_SetSRID(ST_MakePoint(:departure_longitude, :departure_latitude), 4326)::geography, ST_SetSRID(ST_MakePoint(:destination_longitude, :destination_latitude), 4326)::geography, :state, :created_at)",
        );
        $stmt->execute([
            'id' => (string)$ride->id,
            'user_id' => (string)$ride->userId,
            'departure_longitude' => $ride->departureLocation->longitude,
            'departure_latitude' => $ride->departureLocation->latitude,
            'destination_longitude' => $ride->destinationLocation->longitude,
            'destination_latitude' => $ride->destinationLocation->latitude,
            'state' => $ride->state->value,
            'created_at' => $ride->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    protected function makeRide(
        Id $id,
        Id $userId,
        Location $departureLocation,
        Location $destinationLocation,
        State $state,
        DateTimeImmutable $createdAt,
    ): Ride {
        return new Ride(
            id: $id,
            userId: $userId,
            departureLocation: $departureLocation,
            destinationLocation: $destinationLocation,
            driverId: null,
            state: $state,
            createdAt: $createdAt,
        );
    }
}
