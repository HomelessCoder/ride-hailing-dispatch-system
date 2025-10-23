<?php

declare(strict_types=1);

namespace App\Ride\Queue;

use App\CommandQueue\Infra\CommandQueueRepository;
use App\Shared\Id;
use App\Shared\Location\Location;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(name: 'ride:request', description: 'Enqueue a ride request command to the command queue for async processing')]
final class RequestRideConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandQueueRepository $commandQueueRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('user-id', InputArgument::REQUIRED, 'ID of the user requesting the ride')
            ->addArgument('departure-latitude', InputArgument::REQUIRED, 'Latitude of the departure location')
            ->addArgument('departure-longitude', InputArgument::REQUIRED, 'Longitude of the departure location')
            ->addArgument('destination-latitude', InputArgument::REQUIRED, 'Latitude of the destination location')
            ->addArgument('destination-longitude', InputArgument::REQUIRED, 'Longitude of the destination location')
            ->addOption('idempotency-key', 'i', InputOption::VALUE_REQUIRED, 'Optional UUID for idempotency (prevents duplicate ride requests)')
            ->setHelp(<<<'HELP'
                This command enqueues a ride request command to the command queue for async processing.

                <info>Examples:</info>
                  <comment># Request a ride from A to B</comment>
                  bin/console ride:request-ride 0199dfbd-9da7-7e4c-8754-13fa5a9af89c 40.7128 -74.0060 34.0522 -118.2437

                  <comment># Request a ride with custom idempotency key</comment>
                  bin/console ride:request-ride 0199dfbd-9da7-7e4c-8754-13fa5a9af89c 40.7128 -74.0060 34.0522 -118.2437 --idempotency-key=0199dfbd-9da7-7e4c-8754-13fa5a9af123

                <info>Arguments:</info>
                  <comment>user-id</comment>            ID of the user requesting the ride
                  <comment>departure-latitude</comment>  Latitude of the departure location
                  <comment>departure-longitude</comment> Longitude of the departure location
                  <comment>destination-latitude</comment> Latitude of the destination location
                  <comment>destination-longitude</comment> Longitude of the destination location

                <info>Options:</info>
                  <comment>--idempotency-key</comment>  Optional UUID to prevent duplicate ride requests
                HELP);
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rideId = Id::generate();

        if ($input->getOption('idempotency-key')) {
            $rideId = Id::fromString($input->getOption('idempotency-key'));
        }

        $userId = Id::fromString($input->getArgument('user-id'));
        $departureLocation = new Location(
            (float) $input->getArgument('departure-latitude'),
            (float) $input->getArgument('departure-longitude'),
        );
        $destinationLocation = new Location(
            (float) $input->getArgument('destination-latitude'),
            (float) $input->getArgument('destination-longitude'),
        );

        try {
            $id = $this->commandQueueRepo->enqueue(
                new RequestRide(
                    rideId: $rideId,
                    userId: $userId,
                    departureLocation: $departureLocation,
                    destinationLocation: $destinationLocation,
                ),
            );
        } catch (Throwable $e) {
            $io->error('Failed to enqueue command: ' . $e->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('Enqueued RequestRideCommand with id %s', $id));

        return Command::SUCCESS;
    }
}
