<?php

declare(strict_types=1);

namespace App\Ride\Infra;

use App\Infra\AbstractRepository;
use App\Ride\Ride;
use App\Ride\State;
use App\Shared\Id;
use App\Shared\Location\Location;
use DateTimeImmutable;

/**
 * @extends AbstractRepository<Ride>
 */
final class RideRepository extends AbstractRepository
{
    protected function tableName(): string
    {
        return Schema::getTableName();
    }

    protected function getSelectAllSql(): string
    {
        return sprintf(
            'SELECT *,
                ST_X(%1$s::geometry) AS departure_lon,
                ST_Y(%1$s::geometry) AS departure_lat,
                ST_X(%2$s::geometry) AS destination_lon,
                ST_Y(%2$s::geometry) AS destination_lat
            FROM %3$s',
            Schema::DepartureLocation->value,
            Schema::DestinationLocation->value,
            $this->tableName(),
        );
    }

    protected function hydrate(array $data): Ride
    {
        return new Ride(
            id: Id::fromString($data[Schema::Id->value]),
            userId: Id::fromString($data[Schema::UserId->value]),
            departureLocation: new Location(
                latitude: (float)$data['departure_lat'],
                longitude: (float)$data['departure_lon'],
            ),
            destinationLocation: new Location(
                latitude: (float)$data['destination_lat'],
                longitude: (float)$data['destination_lon'],
            ),
            driverId: isset($data[Schema::DriverId->value]) ? Id::fromString($data[Schema::DriverId->value]) : null,
            state: State::from($data[Schema::State->value]),
            createdAt: new DateTimeImmutable($data[Schema::CreatedAt->value]),
        );
    }

    protected function dehydrate(object $entity): array
    {
        return [
            Schema::Id->value => (string)$entity->id,
            Schema::UserId->value => (string)$entity->userId,
            Schema::DepartureLocation->value => sprintf(
                'ST_SetSRID(ST_MakePoint(%F, %F), 4326)::geography',
                $entity->departureLocation->longitude,
                $entity->departureLocation->latitude,
            ),
            Schema::DestinationLocation->value => sprintf(
                'ST_SetSRID(ST_MakePoint(%F, %F), 4326)::geography',
                $entity->destinationLocation->longitude,
                $entity->destinationLocation->latitude,
            ),
            Schema::DriverId->value => $entity->driverId !== null ? (string)$entity->driverId : null,
            Schema::State->value => $entity->state->value,
            Schema::CreatedAt->value => $entity->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
