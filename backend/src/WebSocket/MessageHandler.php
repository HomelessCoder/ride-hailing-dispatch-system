<?php

declare(strict_types=1);

namespace App\WebSocket;

use App\CommandQueue\Infra\CommandQueueRepository;
use App\Driver\DriverService;
use App\Driver\Queue\UpdateDriverLocationCommand;
use App\Driver\Queue\UpdateDriverStatusCommand;
use App\Driver\Status;
use App\Ride\Queue\AcceptRideCommand;
use App\Ride\Queue\CompleteRideCommand;
use App\Ride\Queue\RejectRideCommand;
use App\Ride\Queue\RequestRide;
use App\Ride\Queue\StartRideCommand;
use App\Ride\Quote\QuoteService;
use App\Shared\Id;
use App\Shared\Location\Location;
use Ratchet\ConnectionInterface;

final class MessageHandler
{
    public function __construct(
        private readonly CommandQueueRepository $commandQueueRepository,
        private readonly WebSocketHandler $webSocketHandler,
        private readonly QuoteService $quoteService,
        private readonly DriverService $driverService,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function handle(ConnectionInterface $conn, array $data): void
    {
        $type = $data['type'];

        match ($type) {
            'auth_user' => $this->handleAuthUser($conn, $data),
            'auth_driver' => $this->handleAuthDriver($conn, $data),
            'request_quote' => $this->handleRequestQuote($conn, $data),
            'request_ride' => $this->handleRequestRide($data),
            'accept_ride' => $this->handleAcceptRide($data),
            'reject_ride' => $this->handleRejectRide($data),
            'start_ride' => $this->handleStartRide($data),
            'complete_ride' => $this->handleCompleteRide($data),
            'update_location' => $this->handleUpdateLocation($conn, $data),
            'update_status' => $this->handleUpdateStatus($conn, $data),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleAuthUser(ConnectionInterface $conn, array $data): void
    {
        if (!isset($data['user_id'])) {
            return;
        }

        $this->webSocketHandler->registerUser($data['user_id'], $conn);
        $conn->send(json_encode(['type' => 'auth_success', 'role' => 'user']) ?: '{}');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleAuthDriver(ConnectionInterface $conn, array $data): void
    {
        if (!isset($data['driver_id'])) {
            return;
        }

        // Fetch the driver from the database
        $driver = $this->driverService->getDriver(Id::fromString($data['driver_id']));
        
        if ($driver === null) {
            $conn->send(json_encode([
                'type' => 'auth_error',
                'role' => 'driver',
                'error' => 'Driver not found',
            ]) ?: '{}');
            return;
        }
        
        $this->webSocketHandler->registerDriver($data['driver_id'], $conn);
        
        $conn->send(json_encode([
            'type' => 'auth_success',
            'role' => 'driver',
            'current_location' => [
                'lat' => $driver->currentLocation->latitude,
                'lon' => $driver->currentLocation->longitude,
            ],
            'status' => $driver->status->value,
        ]) ?: '{}');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleRequestQuote(ConnectionInterface $conn, array $data): void
    {
        if (!isset($data['user_id'], $data['departure_lat'], $data['departure_lon'], $data['destination_lat'], $data['destination_lon'])) {
            $conn->send(json_encode([
                'type' => 'quote_error',
                'error' => 'Missing required fields',
            ]) ?: '{}');

            return;
        }

        try {
            $departureLocation = new Location(
                latitude: $data['departure_lat'],
                longitude: $data['departure_lon'],
            );

            $destinationLocation = new Location(
                latitude: $data['destination_lat'],
                longitude: $data['destination_lon'],
            );

            $quote = $this->quoteService->createQuote($departureLocation, $destinationLocation);

            $this->webSocketHandler->sendToUser($data['user_id'], [
                'type' => 'quote_received',
                'quote' => [
                    'id' => $quote->id->value,
                    'departure' => [
                        'lat' => $quote->departure->latitude,
                        'lon' => $quote->departure->longitude,
                    ],
                    'destination' => [
                        'lat' => $quote->destination->latitude,
                        'lon' => $quote->destination->longitude,
                    ],
                    'distance' => $quote->distance->toKilometers(),
                    'duration' => $quote->duration->toMinutes(),
                    'fare' => [
                        'amount' => $quote->fare->amount,
                        'currency' => $quote->fare->currency->value,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            $conn->send(json_encode([
                'type' => 'quote_error',
                'error' => $e->getMessage(),
            ]) ?: '{}');
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleRequestRide(array $data): void
    {
        $command = new RequestRide(
            rideId: Id::generate(),
            userId: Id::fromString($data['user_id']),
            departureLocation: new Location(
                latitude: $data['departure_lat'],
                longitude: $data['departure_lon'],
            ),
            destinationLocation: new Location(
                latitude: $data['destination_lat'],
                longitude: $data['destination_lon'],
            ),
        );

        $this->commandQueueRepository->enqueue($command);

        // Notify the user that their ride request has been created
        $this->webSocketHandler->sendToUser($data['user_id'], [
            'type' => 'ride_requested',
            'ride_id' => $command->rideId->value,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleAcceptRide(array $data): void
    {
        $command = new AcceptRideCommand(
            rideId: Id::fromString($data['ride_id']),
            driverId: Id::fromString($data['driver_id']),
        );

        $this->commandQueueRepository->enqueue($command);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleRejectRide(array $data): void
    {
        $command = new RejectRideCommand(
            rideId: Id::fromString($data['ride_id']),
            driverId: Id::fromString($data['driver_id']),
        );

        $this->commandQueueRepository->enqueue($command);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleStartRide(array $data): void
    {
        $command = new StartRideCommand(
            rideId: Id::fromString($data['ride_id']),
            driverId: Id::fromString($data['driver_id']),
        );

        $this->commandQueueRepository->enqueue($command);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleCompleteRide(array $data): void
    {
        $command = new CompleteRideCommand(
            rideId: Id::fromString($data['ride_id']),
            driverId: Id::fromString($data['driver_id']),
        );

        $this->commandQueueRepository->enqueue($command);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleUpdateLocation(ConnectionInterface $conn, array $data): void
    {
        if (!isset($data['driver_id'], $data['lat'], $data['lon'])) {
            $conn->send(json_encode([
                'type' => 'location_update_error',
                'error' => 'Missing required fields',
            ]) ?: '{}');

            return;
        }

        try {
            $command = new UpdateDriverLocationCommand(
                driverId: Id::fromString($data['driver_id']),
                location: new Location(
                    latitude: $data['lat'],
                    longitude: $data['lon'],
                ),
            );

            $this->commandQueueRepository->enqueue($command);

            $conn->send(json_encode([
                'type' => 'location_update_queued',
                'driver_id' => $data['driver_id'],
            ]) ?: '{}');
        } catch (\Exception $e) {
            $conn->send(json_encode([
                'type' => 'location_update_error',
                'error' => $e->getMessage(),
            ]) ?: '{}');
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleUpdateStatus(ConnectionInterface $conn, array $data): void
    {
        if (!isset($data['driver_id'], $data['status'])) {
            $conn->send(json_encode([
                'type' => 'status_update_error',
                'error' => 'Missing required fields',
            ]) ?: '{}');

            return;
        }

        try {
            // Validate status value
            $status = Status::tryFrom($data['status']);
            if ($status === null) {
                $conn->send(json_encode([
                    'type' => 'status_update_error',
                    'error' => 'Invalid status. Must be one of: available, busy, offline',
                ]) ?: '{}');

                return;
            }

            $command = new UpdateDriverStatusCommand(
                driverId: Id::fromString($data['driver_id']),
                status: $status,
            );

            $this->commandQueueRepository->enqueue($command);

            $conn->send(json_encode([
                'type' => 'status_update_queued',
                'driver_id' => $data['driver_id'],
                'status' => $status->value,
            ]) ?: '{}');
        } catch (\Exception $e) {
            $conn->send(json_encode([
                'type' => 'status_update_error',
                'error' => $e->getMessage(),
            ]) ?: '{}');
        }
    }
}
