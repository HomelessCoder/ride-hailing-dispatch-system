<?php

declare(strict_types=1);

namespace App\WebSocket;

use App\CommandQueue\CommandQueueModule;
use App\CommandQueue\Infra\CommandQueueRepository;
use App\Driver\DriverModule;
use App\Driver\DriverService;
use App\Ride\Quote\QuoteService;
use App\Ride\RideModule;
use Modular\Framework\Container\ConfigurableContainerInterface;
use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\PowerModule\Contract\ImportsComponents;
use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\PowerModule\ImportItem;

final class WebSocketModule implements PowerModule, ExportsComponents, ImportsComponents
{
    public static function exports(): array
    {
        return [
            WebSocketHandler::class,
            MessageHandler::class,
        ];
    }

    public static function imports(): array
    {
        return [
            ImportItem::create(CommandQueueModule::class, CommandQueueRepository::class),
            ImportItem::create(RideModule::class, QuoteService::class),
            ImportItem::create(DriverModule::class, DriverService::class),
        ];
    }

    public function register(ConfigurableContainerInterface $container): void
    {
        $this
            ->webSocketHandler($container)
            ->messageHandler($container)
            ->redisSubscriber($container)
        ;
    }

    private function webSocketHandler(ConfigurableContainerInterface $container): self
    {
        $container->set(
            WebSocketHandler::class,
            WebSocketHandler::class,
        );

        return $this;
    }

    private function messageHandler(ConfigurableContainerInterface $container): self
    {
        $container->set(
            MessageHandler::class,
            MessageHandler::class,
        )->addArguments([
            CommandQueueRepository::class,
            WebSocketHandler::class,
            QuoteService::class,
            DriverService::class,
        ]);

        return $this;
    }

    private function redisSubscriber(ConfigurableContainerInterface $container): self
    {
        $container->set(
            RedisSubscriber::class,
            static fn () => null, // Will be instantiated manually in websocket-server
        );

        return $this;
    }
}
