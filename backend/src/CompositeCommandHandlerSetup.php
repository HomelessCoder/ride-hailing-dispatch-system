<?php

declare(strict_types=1);

namespace App;

use App\CommandQueue\CompositeCommandHandler;
use Modular\Framework\PowerModule\Contract\ExportsComponents;
use Modular\Framework\PowerModule\Contract\PowerModuleSetup;
use Modular\Framework\PowerModule\Setup\PowerModuleSetupDto;
use Modular\Framework\PowerModule\Setup\SetupPhase;
use App\Shared\ICommandHandler;

final class CompositeCommandHandlerSetup implements PowerModuleSetup
{
    private ?CompositeCommandHandler $compositeCommandHandler = null;

    public function setup(PowerModuleSetupDto $powerModuleSetupDto): void
    {
        if ($powerModuleSetupDto->setupPhase === SetupPhase::Pre) {
            return;
        }

        if ($powerModuleSetupDto->powerModule instanceof ExportsComponents === false) {
            return;
        }

        /** @var CompositeCommandHandler $compositeCommandHandler */
        $compositeCommandHandler = $this->compositeCommandHandler ??= $powerModuleSetupDto->rootContainer->get(CompositeCommandHandler::class);

        foreach ($powerModuleSetupDto->powerModule::exports() as $exportedComponent) {
            if (is_a($exportedComponent, ICommandHandler::class, true)) {
                /** @var class-string<ICommandHandler<object>> $exportedComponent */
                $compositeCommandHandler->addCommandHandler(
                    $powerModuleSetupDto->rootContainer->get($exportedComponent),
                );
            }
        }
    }
}
