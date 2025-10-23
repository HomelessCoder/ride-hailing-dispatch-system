<?php

declare(strict_types=1);

namespace App\CommandQueue;

use App\Shared\ICommandHandler;

/**
 * @implements ICommandHandler<object>
 */
final class CompositeCommandHandler implements ICommandHandler
{
    /**
     * @var array<string,ICommandHandler<object>> $commandHandlers
     */
    private array $commandHandlers = [];

    public function handle(object $command): void
    {
        foreach ($this->commandHandlers as $handler) {
            $handler->handle($command);
        }
    }

    /**
     * @param ICommandHandler<object> ...$handlers
     */
    public function addCommandHandler(ICommandHandler ...$handlers): void
    {
        foreach ($handlers as $handler) {
            if ($handler instanceof self) {
                continue;
            }

            $this->commandHandlers[$handler::class] = $handler;
        }
    }
}
