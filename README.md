# Ride-Hailing Dispatch System

A real-time ride-hailing platform demonstrating backend dispatch logic with WebSocket-based communication, asynchronous job processing, and geospatial queries.

## Overview

This system implements core ride-hailing functionality similar to Uber/Lyft, featuring:

- **Backend** (PHP 8.4): WebSocket server, command queue worker, PostGIS-powered driver matching, and Redis pub/sub event system
- **Passenger UI** (Angular 20): Request rides, get fare quotes, track ride status in real-time
- **Driver UI** (Angular 20): Receive ride requests, accept/reject rides, manage availability and location

## Key Features

- 🚗 **Smart Dispatch**: Automatically assigns closest available driver using PostGIS geospatial queries
- ⚡ **Real-time Updates**: WebSocket connections provide instant bidirectional communication
- 🔄 **Asynchronous Processing**: Command queue handles ride dispatching without blocking
- 🗺️ **Location-based Matching**: Efficient distance calculations with PostGIS
- 🔁 **Retry Logic**: Automatically finds alternative drivers if rides are rejected

## Components

- **`backend/`** - PHP backend with WebSocket server, worker processes, and PostgreSQL database
- **`driver-ui/`** - Angular application for drivers to manage rides and availability
- **`passenger-ui/`** - Angular application for passengers to request and track rides

## Running the Application

### Option 1: One Command (Recommended for Reviewers)

Start all services together from the root directory:

```bash
docker-compose up
```

This will start:
- Backend services (PostgreSQL, Redis, WebSocket server, Queue worker)
- Passenger UI at **http://localhost:4201**
- Driver UI at **http://localhost:4202**

To run in detached mode:
```bash
docker-compose up -d
```

To stop all services:
```bash
docker-compose down -v
```

## Detailed Documentation

For detailed setup instructions, configuration options, and troubleshooting, refer to:
- [Backend README](backend/README.md)
- [Driver UI README](driver-ui/README.md)
- [Passenger UI README](passenger-ui/README.md)
