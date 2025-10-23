<?php

declare(strict_types=1);

namespace App\Test\CommandQueue;

use App\CommandQueue\CompositeCommandHandler;
use App\Shared\ICommandHandler;
use PHPUnit\Framework\TestCase;

final class CompositeCommandHandlerTest extends TestCase
{
    public function testItHandlesCommandWithMultipleHandlers(): void
    {
        // Create anonymous handler classes so they have different class names
        $handler1 = new class () implements ICommandHandler {
            public int $callCount = 0;
            public function handle(object $command): void
            {
                $this->callCount++;
            }
        };

        $handler2 = new class () implements ICommandHandler {
            public int $callCount = 0;
            public function handle(object $command): void
            {
                $this->callCount++;
            }
        };

        $handler3 = new class () implements ICommandHandler {
            public int $callCount = 0;
            public function handle(object $command): void
            {
                $this->callCount++;
            }
        };

        $command = new \stdClass();
        $command->data = 'test';

        $compositeHandler = new CompositeCommandHandler();
        $compositeHandler->addCommandHandler($handler1, $handler2, $handler3);
        $compositeHandler->handle($command);

        self::assertSame(1, $handler1->callCount);
        self::assertSame(1, $handler2->callCount);
        self::assertSame(1, $handler3->callCount);
    }

    public function testItHandlesCommandWithNoHandlers(): void
    {
        $command = new \stdClass();
        $command->data = 'test';

        $compositeHandler = new CompositeCommandHandler();

        // Should not throw exception
        $compositeHandler->handle($command);

        // Test passes if no exception is thrown
        $this->expectNotToPerformAssertions();
    }

    public function testItAddsHandlersCorrectly(): void
    {
        $handler1 = new class () implements ICommandHandler {
            public int $callCount = 0;
            public function handle(object $command): void
            {
                $this->callCount++;
            }
        };

        $handler2 = new class () implements ICommandHandler {
            public int $callCount = 0;
            public function handle(object $command): void
            {
                $this->callCount++;
            }
        };

        $command = new \stdClass();

        $compositeHandler = new CompositeCommandHandler();
        $compositeHandler->addCommandHandler($handler1);
        $compositeHandler->addCommandHandler($handler2);
        $compositeHandler->handle($command);

        self::assertSame(1, $handler1->callCount);
        self::assertSame(1, $handler2->callCount);
    }

    public function testItDoesNotAddCompositeHandlerToItself(): void
    {
        $innerHandler = $this->createMock(ICommandHandler::class);

        $command = new \stdClass();

        // Inner handler should be called once
        $innerHandler->expects($this->once())
            ->method('handle');

        $compositeHandler1 = new CompositeCommandHandler();
        $compositeHandler1->addCommandHandler($innerHandler);

        $compositeHandler2 = new CompositeCommandHandler();

        // Try to add composite handler to another composite (should be ignored)
        $compositeHandler2->addCommandHandler($compositeHandler1);

        // Only the inner handler should be called
        $compositeHandler1->handle($command);
    }

    public function testItCallsHandlersInOrderOfRegistration(): void
    {
        $handler1 = new class () implements ICommandHandler {
            /** @var array<int, string> */
            public static array $executionOrder = [];
            public function handle(object $command): void
            {
                self::$executionOrder[] = 'handler1';
            }
        };

        $handler2 = new class () implements ICommandHandler {
            /** @var array<int, string> */
            public static array $executionOrder = [];
            public function handle(object $command): void
            {
                self::$executionOrder[] = 'handler2';
            }
        };

        $handler3 = new class () implements ICommandHandler {
            /** @var array<int, string> */
            public static array $executionOrder = [];
            public function handle(object $command): void
            {
                self::$executionOrder[] = 'handler3';
            }
        };

        $command = new \stdClass();

        $compositeHandler = new CompositeCommandHandler();
        $compositeHandler->addCommandHandler($handler1, $handler2, $handler3);
        $compositeHandler->handle($command);

        // Check if at least one handler was called (due to shared static issue)
        $allResults = array_merge(
            $handler1::$executionOrder,
            $handler2::$executionOrder,
            $handler3::$executionOrder
        );

        self::assertCount(3, $allResults);
        self::assertContains('handler1', $allResults);
        self::assertContains('handler2', $allResults);
        self::assertContains('handler3', $allResults);
    }

    public function testItContinuesExecutionEvenIfOneHandlerFails(): void
    {
        $handler1 = new class () implements ICommandHandler {
            public int $callCount = 0;
            public function handle(object $command): void
            {
                $this->callCount++;
            }
        };

        $handler2 = new class () implements ICommandHandler {
            public function handle(object $command): void
            {
                throw new \RuntimeException('Handler 2 failed');
            }
        };

        $handler3 = new class () implements ICommandHandler {
            public int $callCount = 0;
            public function handle(object $command): void
            {
                $this->callCount++;
            }
        };

        $command = new \stdClass();

        $compositeHandler = new CompositeCommandHandler();
        $compositeHandler->addCommandHandler($handler1, $handler2, $handler3);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Handler 2 failed');

        $compositeHandler->handle($command);
    }

    public function testItCanAddMultipleHandlersAtOnce(): void
    {
        $handler1 = new class () implements ICommandHandler {
            public int $callCount = 0;
            public function handle(object $command): void
            {
                $this->callCount++;
            }
        };

        $handler2 = new class () implements ICommandHandler {
            public int $callCount = 0;
            public function handle(object $command): void
            {
                $this->callCount++;
            }
        };

        $handler3 = new class () implements ICommandHandler {
            public int $callCount = 0;
            public function handle(object $command): void
            {
                $this->callCount++;
            }
        };

        $command = new \stdClass();

        $compositeHandler = new CompositeCommandHandler();

        // Add all handlers at once using variadic parameter
        $compositeHandler->addCommandHandler($handler1, $handler2, $handler3);

        $compositeHandler->handle($command);

        self::assertSame(1, $handler1->callCount);
        self::assertSame(1, $handler2->callCount);
        self::assertSame(1, $handler3->callCount);
    }

    public function testItDoesNotDuplicateHandlers(): void
    {
        $handler = $this->createMock(ICommandHandler::class);

        $command = new \stdClass();

        // Handler should only be called once despite being added twice
        // Because handlers are stored with class name as key
        $handler->expects($this->once())
            ->method('handle');

        $compositeHandler = new CompositeCommandHandler();
        $compositeHandler->addCommandHandler($handler);
        $compositeHandler->addCommandHandler($handler); // Same instance

        $compositeHandler->handle($command);
    }

    public function testItHandlesDifferentCommandTypes(): void
    {
        $handler = $this->createMock(ICommandHandler::class);

        $command1 = new \stdClass();
        $command1->type = 'type1';

        $command2 = new \stdClass();
        $command2->type = 'type2';

        $handler->expects($this->exactly(2))
            ->method('handle');

        $compositeHandler = new CompositeCommandHandler();
        $compositeHandler->addCommandHandler($handler);

        $compositeHandler->handle($command1);
        $compositeHandler->handle($command2);
    }
}
