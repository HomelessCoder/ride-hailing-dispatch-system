<?php

declare(strict_types=1);

namespace App\CommandQueue;

interface ICommandQueueWorker
{
    /**
     * Process pending commands from the queue
     *
     * @return int Number of commands processed
     */
    public function process(): int;
}
