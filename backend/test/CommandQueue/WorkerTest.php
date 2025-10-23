<?php

declare(strict_types=1);

namespace App\Test\CommandQueue;

use App\CommandQueue\Worker;
use App\CommandQueue\CommandQueue;
use App\CommandQueue\CommandQueueStatus;
use App\CommandQueue\Infra\CommandQueueRepository;
use App\Shared\ICommandHandler;
use App\Shared\Id;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Exception;

// Test command classes that can be serialized
class TestCommand
{
    public function __construct(public string $data = 'test')
    {
    }
}

class FirstTestCommand
{
    public function __construct(public string $value = 'first')
    {
    }
}

class SecondTestCommand
{
    public function __construct(public string $value = 'second')
    {
    }
}

class FailingTestCommand
{
    public function __construct(public string $data = 'failing')
    {
    }
}

class RetryTestCommand
{
    public function __construct(public string $data = 'retry')
    {
    }
}

class FailCommand
{
    public function __construct(public string $data = 'fail')
    {
    }
}

class SuccessCommand
{
    public function __construct(public string $data = 'success')
    {
    }
}

class WorkerTest extends TestCase
{
    private CommandQueueRepository&MockObject $repository;
    /** @var ICommandHandler<object>&MockObject */
    private ICommandHandler&MockObject $commandHandler;
    /** @var Worker<object> */
    private Worker $worker;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CommandQueueRepository::class);
        $this->commandHandler = $this->createMock(ICommandHandler::class);
        $this->worker = new Worker($this->repository, $this->commandHandler);
    }

    public function testProcessWithNoCommandsReturnsZero(): void
    {
        $this->repository->expects($this->once())
            ->method('beginTransaction');

        $this->repository->expects($this->once())
            ->method('getPendingCommandsForProcessing')
            ->with('any', 5)
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('commit');

        $this->commandHandler->expects($this->never())
            ->method('handle');

        $processedCount = $this->worker->process();

        $this->assertSame(0, $processedCount);
    }

    public function testProcessSuccessfullyHandlesSingleCommand(): void
    {
        $commandId = Id::generate();
        $command = new TestCommand('test');
        $commandQueue = new CommandQueue(
            id: $commandId,
            status: CommandQueueStatus::Pending,
            type: get_class($command),
            payload: serialize($command),
            attempts: 0,
            lastError: null,
            createdAt: '2025-10-15 10:00:00',
            updatedAt: '2025-10-15 10:00:00'
        );

        $this->repository->expects($this->once())
            ->method('beginTransaction');

        $this->repository->expects($this->once())
            ->method('getPendingCommandsForProcessing')
            ->with('any', 5)
            ->willReturn([$commandQueue]);

        $this->repository->expects($this->exactly(2))
            ->method('updateStatus')
            ->willReturnCallback(function (Id $id, CommandQueueStatus $status) use ($commandId) {
                static $callCount = 0;
                $callCount++;

                $this->assertEquals($commandId, $id);

                if ($callCount === 1) {
                    $this->assertSame(CommandQueueStatus::Processing, $status);
                } elseif ($callCount === 2) {
                    $this->assertSame(CommandQueueStatus::Completed, $status);
                }
            });

        $this->commandHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function ($cmd) {
                return $cmd instanceof TestCommand && $cmd->data === 'test';
            }));

        $this->repository->expects($this->once())
            ->method('commit');

        $processedCount = $this->worker->process();

        $this->assertSame(1, $processedCount);
    }

    public function testProcessHandlesMultipleCommands(): void
    {
        $command1 = new FirstTestCommand('first');
        $command2 = new SecondTestCommand('second');

        $commandQueue1 = new CommandQueue(
            id: Id::generate(),
            status: CommandQueueStatus::Pending,
            type: get_class($command1),
            payload: serialize($command1),
            attempts: 0,
            lastError: null,
            createdAt: '2025-10-15 10:00:00',
            updatedAt: '2025-10-15 10:00:00'
        );

        $commandQueue2 = new CommandQueue(
            id: Id::generate(),
            status: CommandQueueStatus::Pending,
            type: get_class($command2),
            payload: serialize($command2),
            attempts: 0,
            lastError: null,
            createdAt: '2025-10-15 10:00:01',
            updatedAt: '2025-10-15 10:00:01'
        );

        $this->repository->expects($this->once())
            ->method('beginTransaction');

        $this->repository->expects($this->once())
            ->method('getPendingCommandsForProcessing')
            ->with('any', 5)
            ->willReturn([$commandQueue1, $commandQueue2]);

        $this->repository->expects($this->exactly(4))
            ->method('updateStatus');

        $this->commandHandler->expects($this->exactly(2))
            ->method('handle');

        $this->repository->expects($this->once())
            ->method('commit');

        $processedCount = $this->worker->process();

        $this->assertSame(2, $processedCount);
    }

    public function testProcessMarksCommandAsFailedWhenHandlerThrowsAndMaxAttemptsReached(): void
    {
        $commandId = Id::generate();
        $command = new FailingTestCommand('failing');
        $commandQueue = new CommandQueue(
            id: $commandId,
            status: CommandQueueStatus::Pending,
            type: get_class($command),
            payload: serialize($command),
            attempts: 5, // Already at max attempts
            lastError: null,
            createdAt: '2025-10-15 10:00:00',
            updatedAt: '2025-10-15 10:00:00'
        );

        $this->repository->expects($this->once())
            ->method('beginTransaction');

        $this->repository->expects($this->once())
            ->method('getPendingCommandsForProcessing')
            ->with('any', 5)
            ->willReturn([$commandQueue]);

        $this->repository->expects($this->exactly(2))
            ->method('updateStatus')
            ->willReturnCallback(function (Id $id, CommandQueueStatus $status, ?string $error = null) use ($commandId) {
                static $callCount = 0;
                $callCount++;

                $this->assertEquals($commandId, $id);

                if ($callCount === 1) {
                    $this->assertSame(CommandQueueStatus::Processing, $status);
                    $this->assertNull($error);
                } elseif ($callCount === 2) {
                    $this->assertSame(CommandQueueStatus::Failed, $status);
                    $this->assertSame('Handler error', $error);
                }
            });

        $this->commandHandler->expects($this->once())
            ->method('handle')
            ->willThrowException(new Exception('Handler error'));

        $this->repository->expects($this->once())
            ->method('commit');

        $processedCount = $this->worker->process();

        $this->assertSame(0, $processedCount);
    }

    public function testProcessMarksCommandAsPendingWhenHandlerThrowsAndAttemptsRemaining(): void
    {
        $commandId = Id::generate();
        $command = new RetryTestCommand('retry');
        $commandQueue = new CommandQueue(
            id: $commandId,
            status: CommandQueueStatus::Pending,
            type: get_class($command),
            payload: serialize($command),
            attempts: 2, // Less than max attempts
            lastError: null,
            createdAt: '2025-10-15 10:00:00',
            updatedAt: '2025-10-15 10:00:00'
        );

        $this->repository->expects($this->once())
            ->method('beginTransaction');

        $this->repository->expects($this->once())
            ->method('getPendingCommandsForProcessing')
            ->with('any', 5)
            ->willReturn([$commandQueue]);

        $this->repository->expects($this->exactly(2))
            ->method('updateStatus')
            ->willReturnCallback(function (Id $id, CommandQueueStatus $status, ?string $error = null) use ($commandId) {
                static $callCount = 0;
                $callCount++;

                $this->assertEquals($commandId, $id);

                if ($callCount === 1) {
                    $this->assertSame(CommandQueueStatus::Processing, $status);
                    $this->assertNull($error);
                } elseif ($callCount === 2) {
                    $this->assertSame(CommandQueueStatus::Pending, $status);
                    $this->assertSame('Temporary error', $error);
                }
            });

        $this->commandHandler->expects($this->once())
            ->method('handle')
            ->willThrowException(new RuntimeException('Temporary error'));

        $this->repository->expects($this->once())
            ->method('commit');

        $processedCount = $this->worker->process();

        $this->assertSame(0, $processedCount);
    }

    public function testProcessContinuesAfterFailedCommand(): void
    {
        $failingCommand = new FailCommand('fail');
        $successCommand = new SuccessCommand('success');

        $commandQueue1 = new CommandQueue(
            id: Id::generate(),
            status: CommandQueueStatus::Pending,
            type: get_class($failingCommand),
            payload: serialize($failingCommand),
            attempts: 0,
            lastError: null,
            createdAt: '2025-10-15 10:00:00',
            updatedAt: '2025-10-15 10:00:00'
        );

        $commandQueue2 = new CommandQueue(
            id: Id::generate(),
            status: CommandQueueStatus::Pending,
            type: get_class($successCommand),
            payload: serialize($successCommand),
            attempts: 0,
            lastError: null,
            createdAt: '2025-10-15 10:00:01',
            updatedAt: '2025-10-15 10:00:01'
        );

        $this->repository->expects($this->once())
            ->method('beginTransaction');

        $this->repository->expects($this->once())
            ->method('getPendingCommandsForProcessing')
            ->with('any', 5)
            ->willReturn([$commandQueue1, $commandQueue2]);

        // 4 status updates: Processing/Pending for failed, Processing/Completed for success
        $this->repository->expects($this->exactly(4))
            ->method('updateStatus');

        $this->commandHandler->expects($this->exactly(2))
            ->method('handle')
            ->willReturnCallback(function ($cmd) {
                if ($cmd instanceof FailCommand) {
                    throw new Exception('Command failed');
                }
            });

        $this->repository->expects($this->once())
            ->method('commit');

        $processedCount = $this->worker->process();

        // Only the successful command counts
        $this->assertSame(1, $processedCount);
    }

    public function testProcessRollsBackOnRepositoryError(): void
    {
        $this->repository->expects($this->once())
            ->method('beginTransaction');

        $this->repository->expects($this->once())
            ->method('getPendingCommandsForProcessing')
            ->willThrowException(new RuntimeException('Database error'));

        $this->repository->expects($this->once())
            ->method('rollback');

        $this->repository->expects($this->never())
            ->method('commit');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database error');

        $this->worker->process();
    }

    public function testProcessRollsBackWhenCommitFails(): void
    {
        $command = new TestCommand('test');
        $commandQueue = new CommandQueue(
            id: Id::generate(),
            status: CommandQueueStatus::Pending,
            type: get_class($command),
            payload: serialize($command),
            attempts: 0,
            lastError: null,
            createdAt: '2025-10-15 10:00:00',
            updatedAt: '2025-10-15 10:00:00'
        );

        $this->repository->expects($this->once())
            ->method('beginTransaction');

        $this->repository->expects($this->once())
            ->method('getPendingCommandsForProcessing')
            ->with('any', 5)
            ->willReturn([$commandQueue]);

        $this->repository->expects($this->exactly(2))
            ->method('updateStatus');

        $this->commandHandler->expects($this->once())
            ->method('handle');

        $this->repository->expects($this->once())
            ->method('commit')
            ->willThrowException(new RuntimeException('Commit failed'));

        $this->repository->expects($this->once())
            ->method('rollback');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Commit failed');

        $this->worker->process();
    }
}
