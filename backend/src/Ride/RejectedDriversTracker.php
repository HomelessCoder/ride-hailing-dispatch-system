<?php

declare(strict_types=1);

namespace App\Ride;

use App\Shared\Id;
use Predis\Client;

/**
 * Tracks which drivers have rejected a specific ride using Redis with TTL.
 * This prevents re-offering the same ride to drivers who already rejected it.
 */
final class RejectedDriversTracker
{
    private const TTL_SECONDS = 300; // 5 minutes

    public function __construct(
        private readonly Client $redis,
    ) {
    }

    /**
     * Record that a driver rejected a specific ride
     */
    public function recordRejection(Id $rideId, Id $driverId): void
    {
        $key = $this->getKey($rideId);
        
        // Add driver ID to the set of rejected drivers for this ride
        $this->redis->sadd($key, [(string) $driverId]);
        
        // Set TTL on the key (renew it on each rejection)
        $this->redis->expire($key, self::TTL_SECONDS);
    }

    /**
     * Get list of driver IDs who have rejected this ride
     * 
     * @return Id[]
     */
    public function getRejectedDriverIds(Id $rideId): array
    {
        $key = $this->getKey($rideId);
        
        /** @var array<string> $driverIdStrings */
        $driverIdStrings = $this->redis->smembers($key);
        
        return array_map(
            static fn (string $idString) => Id::fromString($idString),
            $driverIdStrings,
        );
    }

    /**
     * Clear rejected drivers for a ride (e.g., when ride is completed/cancelled)
     */
    public function clear(Id $rideId): void
    {
        $key = $this->getKey($rideId);
        $this->redis->del([$key]);
    }

    private function getKey(Id $rideId): string
    {
        return "ride:{$rideId}:rejected_drivers";
    }
}
