<?php

declare(strict_types=1);

namespace App\Ride;

use App\CommandQueue\CommandQueueModule;
use App\CommandQueue\Infra\CommandQueueRepository;
use App\Driver\ClosestDriverFinder;
use App\Driver\DriverModule;
use App\Driver\DriverService;
use App\Infra\InfraModule;
use App\Ride\Infra\RideRepository;
use App\Ride\Queue\AcceptRideCommandHandler;
use App\Ride\Queue\CheckDriverResponseTimeoutCommandHandler;
use App\Ride\Queue\CompleteRideCommandHandler;
use App\Ride\Queue\FindDriverCommandHandler;
use App\Ride\Queue\RejectRideCommandHandler;
use App\Ride\Queue\StartRideCommandHandler;
use App\Ride\Queue\RequestRideCommandHandler;
use App\Ride\Queue\RequestRideConsoleCommand;
use App\Ride\Quote\FareConfiguration;
use App\Ride\Quote\Infra\DistanceCalculator;
use App\Ride\Quote\Infra\DurationEstimator;
use App\Ride\Quote\QuoteCalculator;
use App\Ride\Quote\QuoteService;
use App\Shared\EventPublisher;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\PowerModule\Contract\ImportsComponents;
use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\PowerModule\ImportItem;
use Predis\Client;

final class RideModule implements PowerModule, ExportsComponents, ImportsComponents
{
    public static function exports(): array
    {
        return [
            RideService::class,
            QuoteService::class,

            RequestRideCommandHandler::class,
            FindDriverCommandHandler::class,
            CheckDriverResponseTimeoutCommandHandler::class,
            AcceptRideCommandHandler::class,
            RejectRideCommandHandler::class,
            StartRideCommandHandler::class,
            CompleteRideCommandHandler::class,
            RequestRideConsoleCommand::class,
        ];
    }

    public static function imports(): array
    {
        return [
            ImportItem::create(InfraModule::class, 'repository_pdo_connection'),
            ImportItem::create(InfraModule::class, EventPublisher::class),
            ImportItem::create(InfraModule::class, Client::class),
            ImportItem::create(CommandQueueModule::class, CommandQueueRepository::class),
            ImportItem::create(DriverModule::class, ClosestDriverFinder::class),
            ImportItem::create(DriverModule::class, DriverService::class),
        ];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $this
            ->rideRepository($container)
            ->rideService($container)
            ->rejectedDriversTracker($container)
            ->fareConfiguration($container)
            ->distanceCalculator($container)
            ->durationEstimator($container)
            ->quoteCalculator($container)
            ->quoteService($container)
            ->requestRide($container)
            ->findDriver($container)
            ->checkDriverResponseTimeout($container)
            ->acceptRide($container)
            ->rejectRide($container)
            ->startRide($container)
            ->completeRide($container)
        ;
    }

    private function rideRepository(ConfigurableContainerInterface $container): self
    {
        $container->set(
            RideRepository::class,
            RideRepository::class,
        )->addArguments([
            'repository_pdo_connection',
        ]);

        return $this;
    }

    private function rideService(ConfigurableContainerInterface $container): self
    {
        $container->set(
            RideService::class,
            RideService::class,
        )->addArguments([
            RideRepository::class,
        ]);

        return $this;
    }

    private function rejectedDriversTracker(ConfigurableContainerInterface $container): self
    {
        $container->set(
            RejectedDriversTracker::class,
            RejectedDriversTracker::class,
        )->addArguments([
            Client::class,
        ]);

        return $this;
    }

    private function fareConfiguration(ConfigurableContainerInterface $container): self
    {
        $container->set(
            FareConfiguration::class,
            static fn () => FareConfiguration::createDefault(),
        );

        return $this;
    }

    private function distanceCalculator(ConfigurableContainerInterface $container): self
    {
        $container->set(
            DistanceCalculator::class,
            DistanceCalculator::class,
        )->addArguments([
            'repository_pdo_connection',
        ]);

        return $this;
    }

    private function durationEstimator(ConfigurableContainerInterface $container): self
    {
        $container->set(
            DurationEstimator::class,
            DurationEstimator::class,
        );

        return $this;
    }

    private function quoteCalculator(ConfigurableContainerInterface $container): self
    {
        $container->set(
            QuoteCalculator::class,
            QuoteCalculator::class,
        )->addArguments([
            FareConfiguration::class,
        ]);

        return $this;
    }

    private function quoteService(ConfigurableContainerInterface $container): self
    {
        $container->set(
            QuoteService::class,
            QuoteService::class,
        )->addArguments([
            DistanceCalculator::class,
            DurationEstimator::class,
            QuoteCalculator::class,
        ]);

        return $this;
    }

    private function requestRide(ConfigurableContainerInterface $container): self
    {
        $container->set(
            RequestRideCommandHandler::class,
            RequestRideCommandHandler::class,
        )->addArguments([
            RideService::class,
            CommandQueueRepository::class,
        ]);

        $container->set(
            RequestRideConsoleCommand::class,
            RequestRideConsoleCommand::class,
        )->addArguments([
            CommandQueueRepository::class,
        ]);

        return $this;
    }

    private function findDriver(ConfigurableContainerInterface $container): self
    {
        $container->set(
            FindDriverCommandHandler::class,
            FindDriverCommandHandler::class,
        )->addArguments([
            RideService::class,
            ClosestDriverFinder::class,
            EventPublisher::class,
            CommandQueueRepository::class,
            RejectedDriversTracker::class,
        ]);

        return $this;
    }

    private function checkDriverResponseTimeout(ConfigurableContainerInterface $container): self
    {
        $container->set(
            CheckDriverResponseTimeoutCommandHandler::class,
            CheckDriverResponseTimeoutCommandHandler::class,
        )->addArguments([
            RideService::class,
            CommandQueueRepository::class,
            EventPublisher::class,
            RejectedDriversTracker::class,
        ]);

        return $this;
    }

    private function acceptRide(ConfigurableContainerInterface $container): self
    {
        $container->set(
            AcceptRideCommandHandler::class,
            AcceptRideCommandHandler::class,
        )->addArguments([
            RideService::class,
            DriverService::class,
            EventPublisher::class,
        ]);

        return $this;
    }

    private function rejectRide(ConfigurableContainerInterface $container): self
    {
        $container->set(
            RejectRideCommandHandler::class,
            RejectRideCommandHandler::class,
        )->addArguments([
            RideService::class,
            CommandQueueRepository::class,
            RejectedDriversTracker::class,
        ]);

        return $this;
    }

    private function startRide(ConfigurableContainerInterface $container): self
    {
        $container->set(
            StartRideCommandHandler::class,
            StartRideCommandHandler::class,
        )->addArguments([
            RideService::class,
            EventPublisher::class,
        ]);

        return $this;
    }

    private function completeRide(ConfigurableContainerInterface $container): self
    {
        $container->set(
            CompleteRideCommandHandler::class,
            CompleteRideCommandHandler::class,
        )->addArguments([
            RideService::class,
            DriverService::class,
            EventPublisher::class,
        ]);

        return $this;
    }
}
