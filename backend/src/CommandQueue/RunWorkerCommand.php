<?php

declare(strict_types=1);

namespace App\CommandQueue;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\CommandQueue\ICommandQueueWorker;
use Throwable;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\CommandQueue\Infra\CommandQueueRepository;

#[AsCommand(
    name: 'command-queue:run-worker',
    description: 'Runs the command queue worker to process pending commands.',
)]
final class RunWorkerCommand extends Command
{
    private bool $shouldStop = false;

    public function __construct(
        private readonly CommandQueueRepository $commandQueueRepo,
        private readonly ICommandQueueWorker $worker,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(<<<'HELP'
            This command starts the command queue worker that processes pending commands.

            <info>Usage:</info>
              <comment># Start the command queue worker (runs indefinitely)</comment>
              bin/console command-queue:run-worker

            <info>Features:</info>
              • Runs indefinitely until stopped (Ctrl+C)
              • Processes commands in batches using FOR UPDATE SKIP LOCKED
              • Automatically retries failed commands
              • Graceful shutdown on SIGINT/SIGTERM

            <info>Docker Compose:</info>
              Workers can be defined in docker-compose.yml to start automatically:
              
              command-queue-worker-1:
                build: .
                command: php bin/console command-queue:run-worker
                restart: unless-stopped
            HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Setup signal handlers for graceful shutdown
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, fn () => $this->shouldStop = true);
            pcntl_signal(SIGTERM, fn () => $this->shouldStop = true);
        }

        $io->info(sprintf(
            'Worker started (PID: %d)',
            getmypid()
        ));
        $io->newLine();

        $cyclesRun = 0;
        $totalCommandsProcessed = 0;
        $errorCount = 0;
        $lastReportTime = time();
        $io->text('Waiting for new commands to process...');
        $io->newLine();

        while (!$this->shouldStop) {
            try {
                $commandsProcessed = $this->worker->process();
                $cyclesRun++;
                $totalCommandsProcessed += $commandsProcessed;

                // Log when we actually process a command
                if ($commandsProcessed > 0) {
                    $io->text(sprintf(
                        '[%s] ✓ Processed %d command(s)',
                        date('H:i:s'),
                        $commandsProcessed
                    ));
                }

                // Report stats every 60 seconds
                $currentTime = time();
                if ($currentTime - $lastReportTime >= 60) {
                    $io->text(sprintf(
                        '[%s] Status: %d total commands processed, %d errors',
                        date('H:i:s'),
                        $totalCommandsProcessed,
                        $errorCount
                    ));
                    $lastReportTime = $currentTime;
                }
            } catch (Throwable $e) {
                $errorCount++;
                $io->error(sprintf(
                    'Worker error: %s in %s:%d',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ));

                // Sleep longer on error to avoid rapid failures
                sleep(1);
            }

            $found = $this->commandQueueRepo->listen(10000) !== false;

            if ($found) {
                // NOTE: 1 second delay required due to PostgreSQL replication lag between connections.
                // Even though INSERT is auto-committed before NOTIFY is sent, there's a significant
                // delay (~1s) before the new row becomes visible to the worker's connection.
                // This is likely due to:
                // 1. Separate database connections with independent snapshots
                // 2. PostgreSQL's MVCC requiring snapshot synchronization across connections
                // 3. Possible WAL flush/replication delays in the container environment
                // A smaller delay (10ms) is insufficient; empirically 1s is needed for consistent reads.
                // sleep(1);
                usleep(750000); // 0.5 second delay to reduce idle wait time while ensuring visibility
            }
        }

        $io->warning('Shutdown signal received. Stopping worker gracefully...');
        $io->success(sprintf(
            'Worker stopped. Processed %d commands in %d cycles, encountered %d errors',
            $totalCommandsProcessed,
            $cyclesRun,
            $errorCount
        ));

        return Command::SUCCESS;
    }
}
