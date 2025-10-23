# Driver UI - Ride-Hailing Dispatch System

A real-time driver application built with Angular 20, featuring WebSocket-based communication with the backend dispatch system, live ride requests, and manual location management.

> **⚠️ Technical Assessment Project**
> 
> This application was developed as a technical assessment with time constraints. The current implementation focuses on demonstrating core technical concepts and backend integration rather than production-ready UI/UX. Several trade-offs were made between clean code practices and feature completeness to deliver within the assessment timeline.
>
> **Key Limitations:**
> - **No GPS Integration**: Location is managed manually via text inputs or preset buttons (not real device GPS tracking)
> - **Simplified UI**: The interface is functional but not production-grade; a real-world app would require sophisticated UX design, better error handling, and accessibility features
> - **Limited Validation**: Basic validation only; production apps need comprehensive input sanitization and user guidance
> - **Code Trade-offs**: Some architectural decisions prioritized speed of development over long-term maintainability
>
> This README documents the current implementation as-is while acknowledging these limitations.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Architecture](#architecture)
- [Technology Stack](#technology-stack)
- [System Requirements](#system-requirements)
- [Installation & Setup](#installation--setup)
- [Running the Application](#running-the-application)
- [WebSocket Communication](#websocket-communication)
- [Application Structure](#application-structure)
- [Development](#development)
- [Testing](#testing)

## 🎯 Overview

This is the driver-facing web application for the ride-hailing dispatch system. It provides drivers with:

- **Real-time ride requests** - Instant notifications when passengers request rides
- **Manual location management** - Update coordinates via text inputs or quick-select location presets (no GPS device integration)
- **Status management** - Control availability (available, busy, offline)
- **Ride lifecycle management** - Accept/reject rides, start trips, complete trips
- **Live connection monitoring** - Visual feedback on WebSocket connection status

The application connects to the backend WebSocket server (described in `backend/README.md`) and communicates bidirectionally for real-time updates and command execution.

### Assessment Scope

This UI was built specifically to demonstrate and test the backend dispatch system functionality. For the sake of simplicity and time constraints:

- **Location is simulated**: Drivers manually enter coordinates or click preset buttons instead of using device GPS
- **UI is minimalistic**: The focus is on functional demonstration rather than polished user experience
- **Testing-focused**: The interface prioritizes easy multi-driver testing over production-ready workflows

A production driver application would include:
- Native mobile apps with real GPS tracking
- Sophisticated map integration (Google Maps, Mapbox)
- Turn-by-turn navigation
- Push notifications
- Offline capability
- Advanced error handling and recovery
- Accessibility compliance (WCAG)
- Professional UI/UX design with user research
- Comprehensive form validation and user guidance

## ✨ Features

### Core Functionality

- ✅ **Driver Authentication**: Auto-authenticate with predefined driver IDs
- ✅ **Real-time Ride Requests**: Receive instant notifications with pickup/destination details
- ✅ **Accept/Reject Rides**: Respond to ride requests with visual confirmation
- ✅ **Manual Location Updates**: Update coordinates via text inputs or quick-select preset buttons *(no GPS device integration)*
- ✅ **Status Management**: Toggle between available, busy, and offline states
- ✅ **Ride Progression**: Start ride (passenger picked up) and complete ride workflow
- ✅ **Connection Monitoring**: Real-time WebSocket connection status indicator
- ✅ **Auto-reconnection**: Automatic reconnection with exponential backoff on disconnect

### User Experience (Assessment Scope)

> **Note**: The current UI is intentionally simplified for demonstration and testing purposes. It prioritizes functionality over polish.

- 🎨 **Functional UI**: Basic interface with status-based styling (not production-ready design)
- 📍 **Location Presets**: Quick selection of common London locations for easy testing
- 🔔 **Visual Feedback**: Color-coded status indicators and action buttons
- ⚡ **Desktop-focused**: Primary development target was desktop browsers for testing
- 🔄 **State Persistence**: Restores driver status and location on reconnection

**What's Missing for Production:**
- Professional UI/UX design and branding
- Map visualization (Google Maps, Mapbox integration)
- Real GPS device tracking (Geolocation API)
- Native mobile apps (iOS/Android)
- Comprehensive error states and user guidance
- Loading states and optimistic UI updates
- Accessibility features (screen readers, keyboard navigation)
- Internationalization (i18n)
- Analytics and crash reporting

## 🏗️ Architecture

### System Integration

```
┌─────────────────────┐
│   Driver UI (WEB)   │
│  Angular 20 App     │
│   (Port 4202)       │
└──────────┬──────────┘
           │
           │ WebSocket
           │ (ws://host:8080)
           │
           ▼
┌─────────────────────┐         ┌──────────────────┐
│  WebSocket Server   │◄────────│  Redis PubSub    │
│   (Backend PHP)     │         │  Event Listener  │
└──────────┬──────────┘         └──────────────────┘
           │                            ▲
           │                            │
           ▼                            │
┌─────────────────────┐                 │
│  Command Queue      │                 │
│  (PostgreSQL)       │                 │
└──────────┬──────────┘                 │
           │                            │
           ▼                            │
┌─────────────────────┐                 │
│  Queue Worker       │─────────────────┘
│  (Background)       │  Publishes Events
└─────────────────────┘
```

### Application Flow

1. **Connection**: Driver opens app → WebSocket connects to backend → Auto-authenticates
2. **Status Update**: Driver sets status to "available" → Backend updates database
3. **Ride Request**: Passenger requests ride → Backend finds closest driver → Event published to Redis → WebSocket pushes to driver UI
4. **Accept Ride**: Driver clicks "Accept" → WebSocket sends command → Backend processes → Status changes to "busy"
5. **Start Ride**: Driver clicks "Start Ride" → Backend marks ride as "in progress"
6. **Complete Ride**: Driver clicks "Complete Ride" → Backend finalizes ride → Status returns to "available"

### Component Architecture

```
DriverComponent
    │
    ├── WebsocketService (Singleton)
    │   ├── Connection Management
    │   ├── Message Handling
    │   ├── State Management (RxJS)
    │   └── Event Subscriptions
    │
    ├── Driver State (Observable)
    │   ├── Status (available/busy/offline)
    │   ├── Current Ride
    │   ├── Location
    │   └── Ride Status (heading/passenger_on_board)
    │
    └── UI Components
        ├── Connection Status Indicator
        ├── Driver Info Panel
        ├── Location Controls
        ├── Status Selector
        ├── Ride Request Card
        └── Ride Action Buttons
```

## 🛠️ Technology Stack

- **Angular 20** - Modern web framework with standalone components
- **TypeScript 5.9** - Type-safe development
- **RxJS 7.8** - Reactive programming with observables
- **WebSocket API** - Native browser WebSocket for real-time communication
- **Angular Router** - Multi-driver routing support
- **Angular Forms** - Two-way data binding for location inputs
- **Docker** - Containerized deployment

## 💻 System Requirements

- **Node.js 18+** and npm 9+
- **Angular CLI 20+** (installed globally or via npx)
- Or for containerized deployment:
  - Docker 20.10+ and Docker Compose 2.0+

## 🚀 Installation & Setup

### Using Docker (Recommended)

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd driver-ui
   ```

2. **Install dependencies** (for local development)
   ```bash
   npm install
   ```

3. **Start with Docker Compose**
   ```bash
   docker-compose up -d
   ```

   This starts the driver UI on **port 4202**.

4. **Access the application**
   - Open browser: `http://localhost:4202/driver/1`
   - Available driver IDs: 1, 2, 3, 4
   - Each corresponds to a different driver (Eve, Charlie, Frank, David)

### Local Development Setup

1. **Install dependencies**
   ```bash
   npm install
   ```

2. **Ensure backend is running**
   ```bash
   # In the backend directory
   docker-compose up -d
   ```

3. **Start development server**
   ```bash
   npm start
   # Or: ng serve --host 0.0.0.0 --port 4202
   ```

4. **Access the application**
   - Navigate to `http://localhost:4202/driver/1`

## 🏃 Running the Application

### Docker Compose (Recommended)

```bash
# Start driver UI
docker-compose up -d

# View logs
docker-compose logs -f

# Stop service
docker-compose down
```

### Local Development

```bash
# Development server with hot reload
npm start

# Build for production
npm run build

# Run tests
npm test
```

### Multi-Driver Testing

The application supports testing multiple drivers simultaneously by opening different driver IDs:

- **Driver 1 (Eve)**: `http://localhost:4202/driver/1`
- **Driver 2 (Charlie)**: `http://localhost:4202/driver/2`
- **Driver 3 (Frank)**: `http://localhost:4202/driver/3`
- **Driver 4 (David)**: `http://localhost:4202/driver/4`

Each driver ID corresponds to a pre-seeded driver in the backend database (see `backend/src/Infra/seed.sql`).

### Testing Ride Flow

1. **Setup Driver**
   - Open `http://localhost:4202/driver/1`
   - Wait for connection (green "Connected" indicator)
   - Set status to "Available"
   - Update location to "Downtown London" preset

2. **Request Ride**
   - Open passenger UI at `http://localhost:4201`
   - Request a ride from Downtown London to King's Cross
   - Driver UI will show ride request notification

3. **Accept and Complete Ride**
   - Click "Accept Ride" in driver UI
   - Status automatically changes to "Busy"
   - Click "Start Ride" when passenger is picked up
   - Click "Complete Ride" when destination reached
   - Status returns to "Available"

## 📡 WebSocket Communication

### Connection Details

- **WebSocket URL**: `ws://localhost:8080` (or `ws://host:8080` in Docker)
- **Protocol**: JSON-based message format
- **Authentication**: Automatic on connection with driver ID
- **Reconnection**: Automatic with 3-second retry delay

### Message Format

All messages are JSON with a `type` field:

```typescript
interface WebSocketMessage {
  type: string;
  [key: string]: any;
}
```

### Outgoing Messages (Driver → Backend)

#### Authentication
```json
{
  "type": "auth_driver",
  "driver_id": "019a078d-e95e-75c1-ac7e-6121da5520ed"
}
```

#### Update Location
```json
{
  "type": "update_location",
  "driver_id": "019a078d-e95e-75c1-ac7e-6121da5520ed",
  "lat": 51.5073509,
  "lon": -0.1277583
}
```

#### Update Status
```json
{
  "type": "update_status",
  "driver_id": "019a078d-e95e-75c1-ac7e-6121da5520ed",
  "status": "available"  // or "busy", "offline"
}
```

#### Accept Ride
```json
{
  "type": "accept_ride",
  "ride_id": "019a078d-1234-5678-9abc-def012345678",
  "driver_id": "019a078d-e95e-75c1-ac7e-6121da5520ed"
}
```

#### Reject Ride
```json
{
  "type": "reject_ride",
  "ride_id": "019a078d-1234-5678-9abc-def012345678",
  "driver_id": "019a078d-e95e-75c1-ac7e-6121da5520ed"
}
```

#### Start Ride
```json
{
  "type": "start_ride",
  "ride_id": "019a078d-1234-5678-9abc-def012345678",
  "driver_id": "019a078d-e95e-75c1-ac7e-6121da5520ed"
}
```

#### Complete Ride
```json
{
  "type": "complete_ride",
  "ride_id": "019a078d-1234-5678-9abc-def012345678",
  "driver_id": "019a078d-e95e-75c1-ac7e-6121da5520ed"
}
```

### Incoming Messages (Backend → Driver)

#### Authentication Success
```json
{
  "type": "auth_success",
  "role": "driver",
  "status": "available",
  "current_location": {
    "lat": 51.5073509,
    "lon": -0.1277583
  }
}
```

#### Ride Request (Driver Found Event)
```json
{
  "type": "driver_found",
  "driverId": "019a078d-e95e-75c1-ac7e-6121da5520ed",
  "rideId": "019a078d-1234-5678-9abc-def012345678",
  "userId": "019a078d-9876-5432-1abc-def012345678",
  "departureLocation": {
    "latitude": 51.5073509,
    "longitude": -0.1277583
  },
  "destinationLocation": {
    "latitude": 51.5301,
    "longitude": -0.1232
  },
  "distance": 2500,
  "duration": 600,
  "fare": {
    "amount": 1250,
    "currency": "GBP"
  }
}
```

#### Status Update Confirmation
```json
{
  "type": "status_update_queued",
  "status": "available"
}
```

#### Ride Lifecycle Events
```json
// Ride accepted confirmation
{ "type": "ride_accepted" }

// Ride started confirmation
{ "type": "ride_started" }

// Ride completed confirmation
{ "type": "ride_completed" }

// Ride cancelled by passenger
{ "type": "ride_cancelled" }

// Driver response timeout
{ "type": "driver_ride_request_timeout" }
```

## 📁 Application Structure

```
driver-ui/
├── src/
│   ├── app/
│   │   ├── driver/                    # Driver component (main view)
│   │   │   ├── driver.component.ts    # Component logic
│   │   │   ├── driver.component.html  # Template
│   │   │   └── driver.component.css   # Styles
│   │   ├── services/
│   │   │   └── websocket.service.ts   # WebSocket service (singleton)
│   │   ├── app.component.ts           # Root component
│   │   ├── app.config.ts              # Application configuration
│   │   └── app.routes.ts              # Route definitions
│   ├── index.html                     # HTML entry point
│   ├── main.ts                        # Application bootstrap
│   └── styles.css                     # Global styles
├── public/                            # Static assets
├── angular.json                       # Angular configuration
├── tsconfig.json                      # TypeScript configuration
├── package.json                       # Dependencies and scripts
├── Dockerfile                         # Container image definition
└── docker-compose.yml                 # Service orchestration
```

### Key Files

- **`src/app/services/websocket.service.ts`** - Core WebSocket communication logic
  - Connection management with auto-reconnect
  - Message serialization/deserialization
  - State management with RxJS observables
  - Event handling for all backend messages

- **`src/app/driver/driver.component.ts`** - Main driver interface
  - Driver authentication and routing
  - Location management with presets
  - Ride request handling (accept/reject)
  - Ride lifecycle control (start/complete)
  - Status updates with validation

- **`src/app/app.routes.ts`** - Route configuration
  - Driver-specific routes (`/driver/:id`)
  - Route parameter handling

## 🔧 Development

### Component Communication

The application uses RxJS observables for reactive state management:

```typescript
// WebSocket Service exposes observables
public driverState$: Observable<DriverState>;
public connectionStatus$: Observable<ConnectionStatus>;
public rideRequest$: Observable<RideRequest | null>;
public statusUpdateFeedback$: Observable<FeedbackMessage | null>;

// Component subscribes to state changes
this.websocketService.driverState$.subscribe(state => {
  this.driverState = state;
});
```

### Adding New Message Types

1. **Update WebSocket Service** (`websocket.service.ts`)
   ```typescript
   private handleMessage(data: any): void {
     switch (data.type) {
       case 'new_message_type':
         // Handle new message
         break;
     }
   }
   ```

2. **Update Component** (`driver.component.ts`)
   ```typescript
   // Subscribe to new observable if needed
   this.newSubscription = this.websocketService.newObservable$.subscribe(...);
   ```

3. **Update Template** (`driver.component.html`)
   ```html
   <!-- Add UI elements for new feature -->
   ```

### State Management

Driver state is managed centrally in `WebsocketService`:

```typescript
interface DriverState {
  status: DriverStatus;           // available, busy, offline
  currentRide?: RideRequest;      // Active ride information
  location?: DriverLocation;      // Current GPS coordinates
  rideStatus?: RideStatus;        // heading_to_pickup, passenger_on_board
}
```

State updates are immutable and propagated via BehaviorSubjects:

```typescript
private updateDriverState(state: DriverState): void {
  this.driverStateSubject.next(state);
}
```

### Styling and Themes

Status-based styling uses dynamic CSS classes:

```css
.status-available { background-color: #4caf50; }
.status-busy { background-color: #ff9800; }
.status-offline { background-color: #757575; }
```

Connection status indicator:

```css
.connection-connected { color: #4caf50; }
.connection-disconnected { color: #f44336; }
.connection-connecting { color: #ff9800; }
```

### Location Management

**Important**: This implementation does **NOT** use GPS device tracking. Location is managed through:

1. **Manual Text Input**: Driver enters latitude/longitude values directly
2. **Quick-Select Presets**: Buttons for common London locations

This simplified approach was chosen for the assessment to:
- Facilitate easy multi-driver testing without physical movement
- Reduce complexity and development time
- Focus on backend dispatch logic rather than frontend geolocation APIs

**Predefined Location Presets:**

```typescript
const LOCATION_PRESETS: LocationPreset[] = [
  { name: 'Downtown London', lat: 51.5073509, lon: -0.1277583 },
  { name: 'Heathrow Airport', lat: 51.4700223, lon: -0.4542955 },
  { name: 'King\'s Cross', lat: 51.5301, lon: -0.1232 },
  { name: 'Canary Wharf', lat: 51.5054, lon: -0.0235 },
  { name: 'Westminster', lat: 51.4994, lon: -0.1245 }
];
```

**Production Alternative**: A real driver app would use the browser's Geolocation API (`navigator.geolocation.watchPosition()`) or native mobile GPS for automatic, continuous location tracking.

## 🧪 Testing

### Integration Testing

Test with the full system stack:

```bash
# Start backend services
cd backend
docker-compose up -d

# Start passenger UI
cd passenger-ui
docker-compose up -d

# Start driver UI
cd driver-ui
docker-compose up -d

# Access applications
# Passenger: http://localhost:4201
# Driver 1: http://localhost:4202/driver/1
# Driver 2: http://localhost:4202/driver/2
```

**Test Scenario:**
1. Open Driver 1 at Downtown London (available)
2. Open Driver 2 at Heathrow Airport (available)
3. Request ride from Downtown to King's Cross in passenger UI
4. Verify Driver 1 receives request (closer)
5. Reject in Driver 1
6. Verify Driver 2 receives request (next closest)
7. Accept in Driver 2
8. Complete full ride lifecycle

## 🔍 Troubleshooting

### WebSocket Connection Issues

```bash
# Check if WebSocket server is running
docker-compose -f ../backend/docker-compose.yml ps websocket

# Check WebSocket logs
docker-compose -f ../backend/docker-compose.yml logs websocket

### Docker Issues

```bash
# Rebuild container
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Check container logs
docker-compose logs -f

# Check if port 4202 is available
netstat -an | grep 4202  # Linux/Mac
netstat -an | findstr 4202  # Windows
```

## 📄 License

This is a technical assessment project for demonstration purposes.

## 👤 Author

**Evgenii Teterin**

Developed as a technical assessment to demonstrate:
- Real-time WebSocket communication
- Angular 20 with standalone components
- Reactive state management with RxJS
- Modern TypeScript practices
- Docker containerization
- Integration with PHP backend

## ⚖️ Assessment Trade-offs

Due to time constraints, several trade-offs were made:

### What Was Prioritized ✅
- **Backend Integration**: Solid WebSocket communication and event handling
- **Core Functionality**: All essential features work (accept/reject/start/complete rides)
- **Multi-driver Testing**: Easy to spin up multiple drivers for testing dispatch logic
- **Real-time Updates**: Proper reactive state management with RxJS

### What Was Simplified 🔧
- **Location Tracking**: Manual input instead of GPS integration
- **UI/UX Polish**: Functional but not production-grade design
- **Error Handling**: Basic error messages vs. comprehensive user guidance
- **Code Architecture**: Some components could be further refactored and split
- **Testing Coverage**: Limited unit tests; primarily tested manually
- **Form Validation**: Basic validation vs. comprehensive input sanitization

### What Would Change for Production 🚀
- Native mobile apps (React Native, Flutter, or native iOS/Android)
- Real GPS tracking with background location updates
- Map integration (Google Maps SDK) with live driver markers
- Professional UI/UX design with user research and testing
- Comprehensive error handling and offline support
- Push notifications for ride requests
- Analytics, monitoring, and crash reporting
- Accessibility compliance (WCAG 2.1 AA)
- Full test coverage (unit, integration, E2E)
- Code review and refactoring for maintainability
- Performance optimization and bundle size reduction

---

**Built with ❤️ using Angular 20 and modern web technologies**
