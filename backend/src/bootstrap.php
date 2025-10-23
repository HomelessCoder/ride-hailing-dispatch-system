<?php

declare(strict_types=1);

use App\CommandQueue\CommandQueueModule;
use App\Driver\DriverModule;
use App\Infra\InfraModule;
use App\Ride\RideModule;
use App\User\UserModule;
use App\WebSocket\WebSocketModule;

return [
    InfraModule::class,
    CommandQueueModule::class,
    UserModule::class,
    DriverModule::class,
    RideModule::class,
    WebSocketModule::class,
];
