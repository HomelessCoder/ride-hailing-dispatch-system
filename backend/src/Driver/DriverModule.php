<?php

declare(strict_types=1);

namespace App\Driver;

use App\Driver\Infra\DriverRepository;
use App\Driver\Queue\UpdateDriverLocationCommandHandler;
use App\Driver\Queue\UpdateDriverStatusCommandHandler;
use App\Infra\InfraModule;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\PowerModule\Contract\ImportsComponents;
use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\PowerModule\ImportItem;

final class DriverModule implements PowerModule, ExportsComponents, ImportsComponents
{
    public static function exports(): array
    {
        return [
            DriverService::class,
            ClosestDriverFinder::class,
            UpdateDriverLocationCommandHandler::class,
            UpdateDriverStatusCommandHandler::class,
        ];
    }

    public static function imports(): array
    {
        return [
            ImportItem::create(InfraModule::class, 'repository_pdo_connection'),
        ];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $this
            ->driverRepository($container)
            ->driverService($container)
            ->closestDriverFinder($container)
            ->updateDriverLocationCommandHandler($container)
            ->updateDriverStatusCommandHandler($container)
        ;
    }

    private function driverRepository(\Modular\Framework\Container\ConfigurableContainerInterface $container): self
    {
        $container->set(
            DriverRepository::class,
            DriverRepository::class,
        )->addArguments([
            'repository_pdo_connection',
        ]);

        return $this;
    }

    private function driverService(\Modular\Framework\Container\ConfigurableContainerInterface $container): self
    {
        $container->set(
            DriverService::class,
            DriverService::class,
        )->addArguments([
            DriverRepository::class,
        ]);

        return $this;
    }

    private function closestDriverFinder(\Modular\Framework\Container\ConfigurableContainerInterface $container): self
    {
        $container->set(
            ClosestDriverFinder::class,
            ClosestDriverFinder::class,
        )->addArguments([
            DriverRepository::class,
        ]);

        return $this;
    }

    private function updateDriverLocationCommandHandler(\Modular\Framework\Container\ConfigurableContainerInterface $container): self
    {
        $container->set(
            UpdateDriverLocationCommandHandler::class,
            UpdateDriverLocationCommandHandler::class,
        )->addArguments([
            DriverService::class,
        ]);

        return $this;
    }

    private function updateDriverStatusCommandHandler(\Modular\Framework\Container\ConfigurableContainerInterface $container): self
    {
        $container->set(
            UpdateDriverStatusCommandHandler::class,
            UpdateDriverStatusCommandHandler::class,
        )->addArguments([
            DriverService::class,
        ]);

        return $this;
    }
}
