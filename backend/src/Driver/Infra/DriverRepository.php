<?php

declare(strict_types=1);

namespace App\Driver\Infra;

use App\Driver\Driver;
use App\Driver\Status;
use App\Infra\AbstractRepository;
use App\Shared\Distance;
use App\Shared\Id;
use App\Shared\Location\Location;

/**
 * @extends AbstractRepository<Driver>
 */
class DriverRepository extends AbstractRepository
{
    protected function tableName(): string
    {
        return Schema::getTableName();
    }

    protected function getSelectAllSql(): string
    {
        return sprintf(
            'SELECT *, ST_X(%1$s::geometry) AS lon, ST_Y(%1$s::geometry) AS lat FROM %2$s',
            Schema::CurrentLocation->value,
            $this->tableName(),
        );
    }

    protected function hydrate(array $data): Driver
    {
        return new Driver(
            id: Id::fromString($data[Schema::Id->value]),
            name: $data[Schema::Name->value],
            email: $data[Schema::Email->value],
            currentLocation: new Location(
                latitude: (float)$data['lat'],
                longitude: (float)$data['lon'],
            ),
            status: Status::from($data[Schema::Status->value]),
            updatedAt: new \DateTimeImmutable($data[Schema::UpdatedAt->value]),
        );
    }

    protected function dehydrate(object $entity): array
    {
        return [
            Schema::Id->value => (string)$entity->id,
            Schema::Name->value => $entity->name,
            Schema::Email->value => $entity->email,
            Schema::CurrentLocation->value => sprintf(
                'ST_SetSRID(ST_MakePoint(%F, %F), 4326)::geography',
                $entity->currentLocation->longitude,
                $entity->currentLocation->latitude,
            ),
            Schema::Status->value => $entity->status->value,
            Schema::UpdatedAt->value => $entity->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    public function update(object $entity): bool
    {
        $data = $this->dehydrate($entity);

        // Special handling for PostGIS geometry - don't use placeholder for current_location
        $sql = sprintf(
            'UPDATE %s SET %s = :name, %s = :email, %s = %s, %s = :status, %s = :updated_at WHERE %s = :id',
            $this->tableName(),
            Schema::Name->value,
            Schema::Email->value,
            Schema::CurrentLocation->value,
            $data[Schema::CurrentLocation->value], // Raw SQL for geometry
            Schema::Status->value,
            Schema::UpdatedAt->value,
            Schema::Id->value,
        );

        $params = [
            'id' => $data[Schema::Id->value],
            'name' => $data[Schema::Name->value],
            'email' => $data[Schema::Email->value],
            'status' => $data[Schema::Status->value],
            'updated_at' => $data[Schema::UpdatedAt->value],
        ];

        $statement = $this->connection->prepare($sql);

        return $statement->execute($params);
    }

    /**
     * @param Id[] $excludeDriverIds
     * @param Distance|null $maxDistance Maximum acceptable distance. If null, no distance limit is applied.
     */
    public function findClosestByStatusAndLocation(
        Status $status,
        Location $location,
        array $excludeDriverIds = [],
        ?Distance $maxDistance = null,
    ): ?Driver {
        $whereConditions = [sprintf('%s = :status', Schema::Status->value)];
        $params = [
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'status' => $status->value,
        ];

        // Add exclusion condition if there are drivers to exclude
        if (!empty($excludeDriverIds)) {
            $placeholders = [];
            foreach ($excludeDriverIds as $index => $driverId) {
                $placeholder = "excluded_driver_{$index}";
                $placeholders[] = ":{$placeholder}";
                $params[$placeholder] = (string) $driverId;
            }
            $whereConditions[] = sprintf(
                '%s NOT IN (%s)',
                Schema::Id->value,
                implode(', ', $placeholders),
            );
        }

        // Add distance condition if max distance is specified
        if ($maxDistance !== null) {
            $whereConditions[] = sprintf(
                'ST_Distance(%s, ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326)::geography) <= :max_distance',
                Schema::CurrentLocation->value,
            );
            $params['max_distance'] = $maxDistance->meters;
        }

        $sql = sprintf(
            'SELECT *, 
                ST_X(%1$s::geometry) AS lon, 
                ST_Y(%1$s::geometry) AS lat,
                ST_Distance(
                    %1$s,
                    ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326)::geography
                ) AS distance
            FROM %2$s
            WHERE %3$s
            ORDER BY %1$s <-> ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326)::geography
            LIMIT 1',
            Schema::CurrentLocation->value, // Current location is a geography column
            $this->tableName(),
            implode(' AND ', $whereConditions),
        );

        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        $data = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($data === false) {
            return null;
        }

        return $this->hydrate($data);
    }
}
