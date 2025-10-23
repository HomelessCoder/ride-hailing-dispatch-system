<?php

declare(strict_types=1);

namespace App\Shared;

use Predis\Client;

final class EventPublisher
{
    public function __construct(
        private readonly Client $redis,
    ) {
    }

    public function publish(string $channel, object $event): void
    {
        $this->redis->publish($channel, serialize($event));
    }
}
