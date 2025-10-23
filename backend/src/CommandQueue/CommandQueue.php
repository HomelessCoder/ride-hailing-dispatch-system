<?php

declare(strict_types=1);

namespace App\CommandQueue;

use App\Shared\Id;

final readonly class CommandQueue
{
    public function __construct(
        public Id $id,
        public CommandQueueStatus $status,
        public string $type,
        public string $payload,
        public int $attempts,
        public ?string $lastError,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
