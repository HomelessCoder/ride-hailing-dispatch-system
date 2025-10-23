<?php

declare(strict_types=1);

namespace App\Shared;

use Exception;

/**
 * @template T of object
 */
interface ICommandHandler
{
    /**
     * @param T $command
     * @throws Exception
     */
    public function handle(object $command): void;
}
