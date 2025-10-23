<?php

declare(strict_types=1);

namespace App\WebSocket;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

final class WebSocketHandler implements MessageComponentInterface
{
    /** @var \SplObjectStorage<ConnectionInterface, object> */
    private \SplObjectStorage $connections;
    /** @var array<string, ConnectionInterface> */
    private array $userConnections = [];
    /** @var array<string, ConnectionInterface> */
    private array $driverConnections = [];
    private ?MessageHandler $messageHandler = null;

    public function __construct()
    {
        $this->connections = new \SplObjectStorage();
    }

    public function setMessageHandler(MessageHandler $messageHandler): void
    {
        $this->messageHandler = $messageHandler;
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->connections->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        if ($this->messageHandler === null) {
            return;
        }

        $data = json_decode($msg, true);

        if (!is_array($data) || !isset($data['type'])) {
            return;
        }

        $this->messageHandler->handle($from, $data);
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->connections->detach($conn);

        foreach ($this->userConnections as $userId => $connection) {
            if ($connection === $conn) {
                unset($this->userConnections[$userId]);
            }
        }

        foreach ($this->driverConnections as $driverId => $connection) {
            if ($connection === $conn) {
                unset($this->driverConnections[$driverId]);
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $conn->close();
    }

    public function registerUser(string $userId, ConnectionInterface $conn): void
    {
        $this->userConnections[$userId] = $conn;
    }

    public function registerDriver(string $driverId, ConnectionInterface $conn): void
    {
        $this->driverConnections[$driverId] = $conn;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function sendToUser(string $userId, array $data): void
    {
        if (!isset($this->userConnections[$userId])) {
            return;
        }

        $connection = $this->userConnections[$userId];
        if ($this->connections->contains($connection)) {
            $connection->send(json_encode($data) ?: '{}');
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function sendToDriver(string $driverId, array $data): void
    {
        if (!isset($this->driverConnections[$driverId])) {
            return;
        }

        $connection = $this->driverConnections[$driverId];
        if ($this->connections->contains($connection)) {
            $connection->send(json_encode($data) ?: '{}');
        }
    }
}
