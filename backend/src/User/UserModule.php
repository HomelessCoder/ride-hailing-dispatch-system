<?php

declare(strict_types=1);

namespace App\User;

use App\User\Infra\UserRepository;
use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\PowerModule\Contract\ImportsComponents;
use Modular\Framework\PowerModule\Contract\PowerModule;
use Modular\Framework\Container\ConfigurableContainerInterface;
use App\Infra\InfraModule;
use Modular\Framework\PowerModule\ImportItem;

final class UserModule implements PowerModule, ExportsComponents, ImportsComponents
{
    public static function exports(): array
    {
        return [
            UserRepository::class,
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
            ->userRepository($container)
        ;
    }

    private function userRepository(\Modular\Framework\Container\ConfigurableContainerInterface $container): self
    {
        $container->set(
            UserRepository::class,
            UserRepository::class,
        )->addArguments([
            'repository_pdo_connection',
        ]);

        return $this;
    }
}
