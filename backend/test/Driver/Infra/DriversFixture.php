<?php

declare(strict_types=1);

namespace App\Test\Driver\Infra;

use App\Driver\Driver;
use App\Driver\Status;
use App\Shared\Id;
use App\Shared\Location\Location;
use App\Test\Fixture\Driver\Charlie;
use App\Test\Fixture\Driver\David;
use App\Test\Fixture\Driver\Eve;
use App\Test\Fixture\Driver\Frank;
use DateTimeImmutable;

trait DriversFixture
{
    protected function addTestDriversSet(): void
    {
        $this->addTestDriver(new Charlie());
        $this->addTestDriver(new David());
        $this->addTestDriver(new Eve());
        $this->addTestDriver(new Frank());
    }

    protected function addTestDriver(
        Driver $driver,
    ): void {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare(
            "INSERT INTO drivers (id, name, email, current_location, status) 
             VALUES (:id, :name, :email, ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326)::geography, :status)",
        );
        $stmt->execute([
            'id' => $driver->id,
            'name' => $driver->name,
            'email' => $driver->email,
            'longitude' => $driver->currentLocation->longitude,
            'latitude' => $driver->currentLocation->latitude,
            'status' => $driver->status->value,
        ]);
    }

    protected function makeDriver(
        Id $id,
        string $name,
        string $email,
        string $status,
        float $longitude,
        float $latitude,
    ): Driver {
        return new Driver(
            id: $id,
            name: $name,
            email: $email,
            status: Status::from($status),
            currentLocation: new Location($longitude, $latitude),
            updatedAt: new DateTimeImmutable(),
        );
    }
}
