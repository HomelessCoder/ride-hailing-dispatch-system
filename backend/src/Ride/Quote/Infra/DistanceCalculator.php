<?php

declare(strict_types=1);

namespace App\Ride\Quote\Infra;

use App\Shared\Distance;
use App\Shared\Location\Location;
use PDO;

final class DistanceCalculator
{
    public function __construct(
        private readonly PDO $connection,
    ) {
    }

    /**
     * Calculate geodesic distance between two locations using PostGIS ST_Distance
     * Returns distance in meters
     */
    public function calculate(Location $departure, Location $destination): Distance
    {
        $sql = <<<'SQL'
            SELECT ST_Distance(
                ST_SetSRID(ST_MakePoint(:departure_lon, :departure_lat), 4326)::geography,
                ST_SetSRID(ST_MakePoint(:destination_lon, :destination_lat), 4326)::geography
            ) as distance_meters
        SQL;

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'departure_lon' => $departure->longitude,
            'departure_lat' => $departure->latitude,
            'destination_lon' => $destination->longitude,
            'destination_lat' => $destination->latitude,
        ]);

        $result = $stmt->fetch();

        return new Distance((float)$result['distance_meters']);
    }
}
