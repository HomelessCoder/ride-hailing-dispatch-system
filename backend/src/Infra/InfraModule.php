<?php

declare(strict_types=1);

namespace App\Infra;

use App\Shared\EventPublisher;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\PowerModule\Contract\PowerModule;
use PDO;
use Predis\Client;

final class InfraModule implements PowerModule, ExportsComponents
{
    public static function exports(): array
    {
        return [
            'repository_pdo_connection',
            EventPublisher::class,
            Client::class,
        ];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $this
            ->repositoryPdo($container)
            ->redis($container)
            ->eventPublisher($container)
        ;
    }

    private function repositoryPdo(ConfigurableContainerInterface $container): self
    {
        $container->set(
            'repository_pdo_connection',
            PDO::class,
        )->addArguments([
            $_ENV['REPOSITORY_DSN'],
            $_ENV['REPOSITORY_USER'],
            $_ENV['REPOSITORY_PASSWORD'],
        ])->addMethod(
            'setAttribute',
            [PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION],
        )->addMethod(
            'setAttribute',
            [PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC],
        );

        return $this;
    }

    private function redis(ConfigurableContainerInterface $container): self
    {
        $container->set(
            Client::class,
            static fn () => new Client([
                'scheme' => 'tcp',
                'host'   => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                'port'   => $_ENV['REDIS_PORT'] ?? 6379,
                'read_write_timeout' => 0,
            ]),
        );

        return $this;
    }

    private function eventPublisher(ConfigurableContainerInterface $container): self
    {
        $container->set(
            EventPublisher::class,
            EventPublisher::class,
        )->addArguments([
            Client::class,
        ]);

        return $this;
    }
}
