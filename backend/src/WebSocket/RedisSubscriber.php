<?php

declare(strict_types=1);

namespace App\WebSocket;

use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use Exception;

final class RedisSubscriber
{
    private string $buffer = '';

    public function __construct(
        private readonly string $redisHost,
        private readonly int $redisPort,
        private readonly WebSocketHandler $webSocketHandler,
        private readonly LoopInterface $loop,
    ) {
    }

    public function connect(): void
    {
        $connector = new Connector($this->loop);
        
        $connector->connect("tcp://{$this->redisHost}:{$this->redisPort}")->then(
            function (ConnectionInterface $connection) {
                // Subscribe to channels
                $connection->write("*2\r\n$10\r\nPSUBSCRIBE\r\n$6\r\nuser.*\r\n");
                $connection->write("*2\r\n$10\r\nPSUBSCRIBE\r\n$8\r\ndriver.*\r\n");
                
                $connection->on('data', function ($data) {
                    $this->handleRedisData($data);
                });
                
                $connection->on('error', function (\Exception $e) {
                    echo "Redis connection error: {$e->getMessage()}\n";
                });
                
                $connection->on('close', function () {
                    echo "Redis connection closed, reconnecting...\n";
                    // Reconnect after 1 second
                    $this->loop->addTimer(1, function () {
                        $this->connect();
                    });
                });
            },
            // @phpstan-ignore-next-line
            function (Exception $e) {
                echo "Failed to connect to Redis: {$e->getMessage()}\n";
                // Retry after 5 seconds
                $this->loop->addTimer(5, function () {
                    $this->connect();
                });
            }
        );
    }

    private function handleRedisData(string $data): void
    {
        $this->buffer .= $data;
        
        // Parse RESP protocol messages
        while (($message = $this->parseRespArray($this->buffer)) !== null) {
            if (isset($message[0]) && $message[0] === 'pmessage' && count($message) >= 4) {
                $pattern = $message[1];
                $channel = $message[2];
                $payload = $message[3];
                
                $event = @unserialize($payload);
                if ($event === false) {
                    continue;
                }
                
                if (str_starts_with($channel, 'user.')) {
                    $userId = substr($channel, 5);
                    $this->webSocketHandler->sendToUser($userId, $this->formatEvent($event));
                } elseif (str_starts_with($channel, 'driver.')) {
                    $driverId = substr($channel, 7);
                    $this->webSocketHandler->sendToDriver($driverId, $this->formatEvent($event));
                }
            }
        }
    }

    /**
     * @return array<mixed>|null
     */
    private function parseRespArray(string &$buffer): ?array
    {
        if (strlen($buffer) === 0 || $buffer[0] !== '*') {
            return null;
        }
        
        $pos = strpos($buffer, "\r\n");
        if ($pos === false) {
            return null;
        }
        
        $arraySize = (int)substr($buffer, 1, $pos - 1);
        
        // Work with a temporary buffer to avoid corrupting the original buffer
        $tempBuffer = substr($buffer, $pos + 2);
        
        $result = [];
        for ($i = 0; $i < $arraySize; $i++) {
            $element = $this->parseRespElement($tempBuffer);
            if ($element === false) {
                // Not enough data yet, return null without modifying the buffer
                return null;
            }
            $result[] = $element;
        }
        
        // Only update the buffer if we successfully parsed the entire array
        $buffer = $tempBuffer;
        
        return $result;
    }
    
    /**
     * Parse any RESP element (bulk string, integer, simple string, etc.)
     * Returns false if not enough data, null for null bulk strings, or the actual value
     */
    private function parseRespElement(string &$buffer): mixed
    {
        if (strlen($buffer) === 0) {
            return false;
        }
        
        $type = $buffer[0];
        
        // Bulk String: $<length>\r\n<data>\r\n
        if ($type === '$') {
            return $this->parseRespBulkString($buffer);
        }
        
        // Integer: :<number>\r\n
        if ($type === ':') {
            $pos = strpos($buffer, "\r\n");
            if ($pos === false) {
                return false;
            }
            $value = (int)substr($buffer, 1, $pos - 1);
            $buffer = substr($buffer, $pos + 2);
            return $value;
        }
        
        // Simple String: +<string>\r\n
        if ($type === '+') {
            $pos = strpos($buffer, "\r\n");
            if ($pos === false) {
                return false;
            }
            $value = substr($buffer, 1, $pos - 1);
            $buffer = substr($buffer, $pos + 2);
            return $value;
        }
        
        // Error: -<error message>\r\n
        if ($type === '-') {
            $pos = strpos($buffer, "\r\n");
            if ($pos === false) {
                return false;
            }
            $value = substr($buffer, 1, $pos - 1);
            $buffer = substr($buffer, $pos + 2);
            return $value;
        }
        
        // Unknown type
        return false;
    }

    private function parseRespBulkString(string &$buffer): mixed
    {
        if (strlen($buffer) === 0 || $buffer[0] !== '$') {
            return false;
        }
        
        $pos = strpos($buffer, "\r\n");
        if ($pos === false) {
            return false;
        }
        
        $length = (int)substr($buffer, 1, $pos - 1);
        if ($length === -1) {
            $buffer = substr($buffer, $pos + 2);
            return null;
        }
        
        if (strlen($buffer) < $pos + 2 + $length + 2) {
            return false;
        }
        
        $value = substr($buffer, $pos + 2, $length);
        $buffer = substr($buffer, $pos + 2 + $length + 2);
        
        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatEvent(object $event): array
    {
        $eventType = basename(str_replace('\\', '/', get_class($event)));
        
        // Convert event class names to snake_case message types
        // e.g., RideCompletedEvent -> ride_completed
        $messageType = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('Event', '', $eventType)) ?: '');
        
        $data = get_object_vars($event);
        
        // Flatten the data structure for cleaner API
        $payload = ['type' => $messageType];
        
        foreach ($data as $key => $value) {
            if ($value instanceof \App\Shared\Id) {
                $payload[$key] = $value->value;
            } else {
                $payload[$key] = $value;
            }
        }
        
        return $payload;
    }
}
