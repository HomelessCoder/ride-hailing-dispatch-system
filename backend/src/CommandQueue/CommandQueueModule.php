<?php

declare(strict_types=1);

namespace App\CommandQueue;

use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\PowerModule\Contract\PowerModule;
use App\CommandQueue\Infra\CommandQueueRepository;
use PDO;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Pdo\Pgsql;

final class CommandQueueModule implements PowerModule, ExportsComponents
{
    public static function exports(): array
    {
        return [
            CommandQueueRepository::class,
            CompositeCommandHandler::class,
            RunWorkerCommand::class,
        ];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $this
            ->commandQueueRepository($container)
            ->compositeCommandHandler($container)
            ->worker($container)
        ;
    }

    private function commandQueueRepository(ConfigurableContainerInterface $container): self
    {
        $container->set(Pgsql::class, Pgsql::class)
            ->addMethod(
                'setAttribute',
                [PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION],
            )->addMethod(
                'setAttribute',
                [PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC],
            )->addArguments([
                $_ENV['COMMAND_QUEUE_DSN'],
                $_ENV['COMMAND_QUEUE_USER'],
                $_ENV['COMMAND_QUEUE_PASSWORD'],
            ])
        ;

        $container->set(
            CommandQueueRepository::class,
            CommandQueueRepository::class,
        )->addArguments([
            Pgsql::class,
        ]);

        return $this;
    }

    private function compositeCommandHandler(ConfigurableContainerInterface $container): self
    {
        $container->set(
            CompositeCommandHandler::class,
            CompositeCommandHandler::class,
        );

        return $this;
    }

    private function worker(ConfigurableContainerInterface $container): self
    {
        $container->set(
            Worker::class,
            Worker::class,
        )->addArguments([
            CommandQueueRepository::class,
            CompositeCommandHandler::class,
        ]);

        $container->set(
            RunWorkerCommand::class,
            RunWorkerCommand::class,
        )->addArguments([
            CommandQueueRepository::class,
            Worker::class,
        ]);

        return $this;
    }
}
