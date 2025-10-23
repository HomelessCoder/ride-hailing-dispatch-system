# Passenger UI - Ride-Hailing Dispatch System

A real-time passenger application built with Angular 20, featuring WebSocket-based communication with the backend dispatch system, ride quotes, booking, and manual location selection.

> **⚠️ Technical Assessment Project**
> 
> This application was developed as a technical assessment with time constraints. The current implementation focuses on demonstrating core technical concepts and backend integration rather than production-ready UI/UX. Several trade-offs were made between clean code practices and feature completeness to deliver within the assessment timeline.
>
> **Key Limitations:**
> - **No GPS Integration**: Locations are entered manually via text inputs or preset buttons (not real device GPS/address search)
> - **Simplified UI**: The interface is functional but not production-grade; a real-world app would require sophisticated UX design, map integration, and better error handling
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

This is the passenger-facing web application for the ride-hailing dispatch system. It provides passengers with:

- **Quote requests** - Get instant fare estimates with distance and duration
- **Ride booking** - Request rides with real-time driver assignment
- **Manual location selection** - Set pickup and destination via coordinates or quick-select presets (no GPS/address search)
- **Ride tracking** - Live updates on ride status (finding driver, accepted, in progress, completed)
- **Multi-user support** - Switch between test users (Alice and Bob)
- **Live connection monitoring** - Visual feedback on WebSocket connection status

The application connects to the backend WebSocket server (described in `backend/README.md`) and communicates bidirectionally for real-time ride updates and status changes.

### Assessment Scope

This UI was built specifically to demonstrate and test the backend dispatch system functionality. For the sake of simplicity and time constraints:

- **Locations are simulated**: Passengers manually enter coordinates or click preset buttons instead of using device GPS or address search
- **UI is minimalistic**: The focus is on functional demonstration rather than polished user experience
- **Testing-focused**: The interface prioritizes easy multi-passenger testing over production-ready workflows

A production passenger application would include:
- Native mobile apps with real GPS tracking
- Address search and autocomplete (Google Places API)
- Interactive map with pickup/destination markers
- Live driver location tracking on map
- Payment integration (Stripe, PayPal)
- Ride history and receipts
- User profiles and preferences
- Push notifications
- Rating and review system
- Accessibility compliance (WCAG)
- Professional UI/UX design with user research
- Comprehensive form validation and user guidance

## ✨ Features

### Core Functionality

- ✅ **Quote Calculation**: Get instant fare estimates before booking
- ✅ **Ride Booking**: Request rides with automatic driver assignment
- ✅ **Real-time Updates**: Live status changes (finding driver, accepted, in progress, completed)
- ✅ **Manual Location Input**: Set pickup/destination via text inputs or quick-select preset buttons *(no GPS/address search)*
- ✅ **Multi-user Testing**: Switch between Alice and Bob for testing multiple passengers
- ✅ **Progress Tracking**: Visual progress bar showing ride stages
- ✅ **Connection Monitoring**: Real-time WebSocket connection status indicator
- ✅ **Auto-reconnection**: Automatic reconnection with exponential backoff on disconnect

### User Experience (Assessment Scope)

> **Note**: The current UI is intentionally simplified for demonstration and testing purposes. It prioritizes functionality over polish.

- 🎨 **Functional UI**: Single-page interface with state-based views (not production-ready design)
- 📍 **Location Presets**: Quick selection of common London locations for easy testing
- 🔔 **Status Messages**: Clear text feedback for each ride stage
- 📊 **Progress Indicator**: Visual bar showing ride progression
- ⚡ **Desktop-focused**: Primary development target was desktop browsers for testing
- 🔄 **User Switching**: Easy toggle between test users without reconnection

**What's Missing for Production:**
- Professional UI/UX design and branding
- Interactive map with live markers (Google Maps, Mapbox)
- Real GPS device tracking and address search/autocomplete
- Native mobile apps (iOS/Android)
- Payment processing and receipts
- Ride history and past bookings
- User profiles and saved locations
- Rating drivers and feedback system
- Comprehensive error states and user guidance
- Loading states and optimistic UI updates
- Accessibility features (screen readers, keyboard navigation)
- Internationalization (i18n)
- Analytics and user behavior tracking

## 🏗️ Architecture

### System Integration

```
┌─────────────────────┐
│  Passenger UI (WEB) │
│  Angular 20 App     │
│   (Port 4201)       │
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
           │
           ▼
┌─────────────────────┐
│  Driver Assignment  │
│  (PostGIS Query)    │
└─────────────────────┘
```

### Application Flow

1. **Connection**: Passenger opens app → WebSocket connects to backend → Auto-authenticates as Alice (or Bob)
2. **Request Quote**: Passenger enters locations → Clicks "Get Quote" → Backend calculates fare → Quote displayed
3. **Book Ride**: Passenger clicks "Request Ride" → Backend creates ride and enqueues dispatch command
4. **Finding Driver**: Backend worker finds closest available driver → Publishes event
5. **Driver Accepts**: Driver accepts ride → Backend publishes event → Passenger sees "Driver On The Way"
6. **Ride Started**: Driver starts ride → Backend publishes event → Passenger sees "Ride in Progress"
7. **Ride Completed**: Driver completes ride → Backend publishes event → Passenger sees "Ride Completed"

### Component Architecture

```
App Component
    │
    ├── WebsocketService (Singleton)
    │   ├── Connection Management
    │   ├── Message Handling
    │   ├── State Management (RxJS)
    │   └── Event Subscriptions
    │
    ├── Ride Status (Observable)
    │   ├── Status (idle/requesting_quote/quote_received/finding_driver/etc.)
    │   ├── Current Quote
    │   ├── Current Ride ID
    │   └── Status Message
    │
    └── UI Sections
        ├── Connection Status Indicator
        ├── User Selector (Alice/Bob)
        ├── Location Input Controls
        ├── Location Preset Buttons
        ├── Quote Display Card
        ├── Booking Actions
        ├── Ride Progress Tracker
        └── Status Messages
```

## 🛠️ Technology Stack

- **Angular 20** - Modern web framework with standalone components
- **TypeScript 5.9** - Type-safe development
- **RxJS 7.8** - Reactive programming with observables
- **WebSocket API** - Native browser WebSocket for real-time communication
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
   cd passenger-ui
   ```

2. **Install dependencies** (for local development)
   ```bash
   npm install
   ```

3. **Start with Docker Compose**
   ```bash
   docker-compose up -d
   ```

   This starts the passenger UI on **port 4201**.

4. **Access the application**
   - Open browser: `http://localhost:4201`
   - Default user: Alice (can switch to Bob in the UI)

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
   # Or: ng serve --host 0.0.0.0 --port 4201
   ```

4. **Access the application**
   - Navigate to `http://localhost:4201`

## 🏃 Running the Application

### Docker Compose (Recommended)

```bash
# Start passenger UI
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

### Multi-Passenger Testing

The application supports testing multiple passengers simultaneously:

- **Alice**: Default user (ID: `019a078d-e95e-7606-a2d8-b3dfa4bc1934`)
- **Bob**: Alternative user (ID: `019a078d-e95e-78de-9df5-9b4a39281169`)

You can:
1. Open the app in one browser as Alice
2. Open the app in another browser/incognito window and switch to Bob
3. Test concurrent ride requests and driver assignment

Each user ID corresponds to a pre-seeded user in the backend database (see `backend/src/Infra/seed.sql`).

### Testing Complete Ride Flow

**Prerequisites:**
- Backend services running (`cd backend && docker-compose up -d`)
- At least one driver available (`http://localhost:4202/driver/1`)

**Test Scenario:**

1. **Setup Driver**
   - Open driver UI: `http://localhost:4202/driver/1`
   - Set status to "Available"
   - Set location to "Downtown London"

2. **Request Quote (Passenger)**
   - Open passenger UI: `http://localhost:4201`
   - Departure: "Downtown London" (preset button)
   - Destination: "Heathrow Airport" (preset button)
   - Click "Get Quote"
   - Verify quote shows distance, duration, and fare

3. **Book Ride**
   - Click "Request Ride"
   - Status changes to "Finding a Driver"
   - Driver UI receives ride request notification

4. **Driver Accepts**
   - In driver UI, click "Accept Ride"
   - Passenger UI updates to "Driver On The Way!"

5. **Complete Ride**
   - In driver UI, click "Start Ride" (passenger picked up)
   - Passenger UI updates to "Ride in Progress"
   - In driver UI, click "Complete Ride"
   - Passenger UI updates to "Ride Completed!"

6. **Start Over**
   - In passenger UI, click "Start Over"
   - Ready for next booking

## 📡 WebSocket Communication

### Connection Details

- **WebSocket URL**: `ws://localhost:8080` (or `ws://host:8080` in Docker)
- **Protocol**: JSON-based message format
- **Authentication**: Automatic on connection with user ID
- **Reconnection**: Automatic with 3-second retry delay

### Message Format

All messages are JSON with a `type` field:

```typescript
interface WebSocketMessage {
  type: string;
  [key: string]: any;
}
```

### Outgoing Messages (Passenger → Backend)

#### Authentication
```json
{
  "type": "auth_user",
  "user_id": "019a078d-e95e-7606-a2d8-b3dfa4bc1934"
}
```

#### Request Quote
```json
{
  "type": "request_quote",
  "user_id": "019a078d-e95e-7606-a2d8-b3dfa4bc1934",
  "departure_lat": 51.5073509,
  "departure_lon": -0.1277583,
  "destination_lat": 51.4700223,
  "destination_lon": -0.4542955
}
```

#### Request Ride
```json
{
  "type": "request_ride",
  "user_id": "019a078d-e95e-7606-a2d8-b3dfa4bc1934",
  "departure_lat": 51.5073509,
  "departure_lon": -0.1277583,
  "destination_lat": 51.4700223,
  "destination_lon": -0.4542955
}
```

### Incoming Messages (Backend → Passenger)

#### Authentication Success
```json
{
  "type": "auth_success",
  "role": "user"
}
```

#### Quote Received
```json
{
  "type": "quote_received",
  "quote": {
    "id": "019a078d-1234-5678-9abc-def012345678",
    "departure": {
      "lat": 51.5073509,
      "lon": -0.1277583
    },
    "destination": {
      "lat": 51.4700223,
      "lon": -0.4542955
    },
    "distance": 25000,
    "duration": 1800,
    "fare": {
      "amount": 12500,
      "currency": "GBP"
    }
  }
}
```

#### Quote Error
```json
{
  "type": "quote_error",
  "error": "Invalid location coordinates"
}
```

#### Ride Requested
```json
{
  "type": "ride_requested",
  "ride_id": "019a078d-1234-5678-9abc-def012345678"
}
```

#### Ride Lifecycle Events
```json
// Driver accepted ride
{
  "type": "ride_accepted",
  "rideId": "019a078d-1234-5678-9abc-def012345678"
}

// Ride started (passenger picked up)
{
  "type": "ride_started",
  "rideId": "019a078d-1234-5678-9abc-def012345678"
}

// Ride completed
{
  "type": "ride_completed",
  "rideId": "019a078d-1234-5678-9abc-def012345678"
}

// Driver rejected (retry with next driver)
{
  "type": "ride_rejected",
  "rideId": "019a078d-1234-5678-9abc-def012345678"
}

// No drivers available
{
  "type": "no_driver_available",
  "rideId": "019a078d-1234-5678-9abc-def012345678",
  "message": "No drivers available at the moment. Please try again later."
}
```

## 📁 Application Structure

```
passenger-ui/
├── src/
│   ├── app/
│   │   ├── services/
│   │   │   └── websocket.service.ts   # WebSocket service (singleton)
│   │   ├── app.ts                     # Main component logic
│   │   ├── app.html                   # Main template
│   │   ├── app.css                    # Main styles
│   │   └── app.config.ts              # Application configuration
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
  - Quote and ride request methods

- **`src/app/app.ts`** - Main application component
  - User authentication (Alice/Bob switching)
  - Location management with presets
  - Quote request handling
  - Ride booking workflow
  - Status tracking and display logic
  - Progress calculation

- **`src/app/app.html`** - Main template
  - Connection status indicator
  - User selector
  - Location input forms with preset buttons
  - Quote display card
  - Booking actions
  - Ride progress tracker
  - Status messages and completion screen

## 🔧 Development

### Component Communication

The application uses RxJS observables for reactive state management:

```typescript
// WebSocket Service exposes observables
public status$: Observable<RideStatus>;
public connectionStatus$: Observable<ConnectionStatus>;

// Component subscribes to state changes
this.websocketService.status$.subscribe(status => {
  this.currentStatus = status;
  // Update UI based on status
});
```

### Ride Status Flow

The application manages the following status states:

```typescript
type RideStatus = 
  | 'idle'              // No active booking
  | 'requesting_quote'  // Fetching fare estimate
  | 'quote_received'    // Quote ready, can book ride
  | 'booking_requested' // Submitting ride request
  | 'finding_driver'    // Backend searching for driver
  | 'driver_accepted'   // Driver confirmed and heading to pickup
  | 'ride_in_progress'  // Passenger picked up, heading to destination
  | 'completed'         // Ride finished
  | 'error';            // Error occurred
```

### Location Management

**Important**: This implementation does **NOT** use GPS device tracking or address search. Locations are managed through:

1. **Manual Text Input**: User enters latitude/longitude values directly
2. **Quick-Select Presets**: Buttons for common London locations

This simplified approach was chosen for the assessment to:
- Facilitate easy multi-passenger testing without requiring geolocation permissions
- Reduce complexity and development time
- Focus on backend dispatch logic rather than frontend geolocation APIs

**Predefined Location Presets:**

```typescript
// Locations in app.ts
setLocationDowntown(isDeparture: boolean)    // 51.5073509, -0.1277583
setLocationHeathrow(isDeparture: boolean)    // 51.4700223, -0.4542955
setLocationMidtown(isDeparture: boolean)     // 51.5125, -0.1357
setLocationUptown(isDeparture: boolean)      // 51.515419, -0.141588
```

**Production Alternative**: A real passenger app would use:
- Browser Geolocation API (`navigator.geolocation.getCurrentPosition()`) for current location
- Google Places Autocomplete for address search
- Interactive map (Google Maps, Mapbox) for visual selection
- Saved locations (home, work, favorites)

### Adding New Message Types

1. **Update WebSocket Service** (`websocket.service.ts`)
   ```typescript
   private handleMessage(data: any): void {
     switch (data.type) {
       case 'new_message_type':
         // Handle new message
         this.updateStatus({
           status: 'new_status',
           message: 'New status message'
         });
         break;
     }
   }
   ```

2. **Update Component** (`app.ts`)
   ```typescript
   // Handle new status in template logic
   get isNewStatus(): boolean {
     return this.currentStatus.status === 'new_status';
   }
   ```

3. **Update Template** (`app.html`)
   ```html
   <!-- Add UI elements for new status -->
   <div *ngIf="isNewStatus">
     New status content
   </div>
   ```

### Styling and Themes

Status-based styling uses dynamic CSS classes:

```css
.status-connected { color: #4caf50; }
.status-disconnected { color: #f44336; }
.status-connecting { color: #ff9800; }

.progress-bar { background-color: #4caf50; }
.btn-primary { background-color: #2196f3; }
.btn-success { background-color: #4caf50; }
```

## 🧪 Testing

### Integration Testing

Test with the full system stack:

```bash
# Start backend services
cd backend
docker-compose up -d

# Start driver UI
cd driver-ui
docker-compose up -d

# Start passenger UI
cd passenger-ui
docker-compose up -d

# Access applications
# Passenger: http://localhost:4201
# Driver: http://localhost:4202/driver/1
```

**Test Scenarios:**

1. **Quote Flow**
   - Enter locations
   - Request quote
   - Verify quote shows correct distance/duration/fare
   - Change locations
   - Request new quote

2. **Successful Ride**
   - Request quote
   - Book ride
   - Driver accepts
   - Driver starts ride
   - Driver completes ride
   - Verify all status transitions

3. **Driver Rejection**
   - Book ride with multiple drivers available
   - First driver rejects
   - Verify automatic retry with next driver

4. **No Drivers Available**
   - Ensure all drivers are offline or busy
   - Request ride
   - Verify "No drivers available" error message

5. **Multi-User Testing**
   - Open Alice in one browser
   - Open Bob in another browser
   - Request rides from both
   - Verify proper ride assignment to different drivers

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
- **Core Functionality**: Quote requests, ride booking, and status tracking work properly
- **Multi-user Testing**: Easy to test with multiple passengers (Alice/Bob)
- **Real-time Updates**: Proper reactive state management with RxJS
- **Ride Flow**: Complete end-to-end ride lifecycle implementation

### What Was Simplified 🔧
- **Location Input**: Manual coordinates instead of GPS/address search
- **UI/UX Polish**: Functional but not production-grade design
- **Map Integration**: No visual map display (would use Google Maps/Mapbox in production)
- **Payment**: No payment processing (would integrate Stripe/PayPal)
- **Error Handling**: Basic error messages vs. comprehensive user guidance
- **Code Architecture**: Single component vs. modular component structure
- **Testing Coverage**: Limited unit tests; primarily tested manually
- **Form Validation**: Basic validation vs. comprehensive input sanitization

### What Would Change for Production 🚀
- Native mobile apps (React Native, Flutter, or native iOS/Android)
- Real GPS tracking with automatic current location detection
- Google Places API for address search and autocomplete
- Interactive map with live markers for pickup/destination/driver
- Payment integration with saved cards and receipts
- Ride history with past bookings and receipts
- User profiles with saved locations and preferences
- Rating and review system for drivers
- Push notifications for ride updates
- Professional UI/UX design with user research and testing
- Comprehensive error handling and offline support
- Accessibility compliance (WCAG 2.1 AA)
- Full test coverage (unit, integration, E2E)
- Code review and refactoring for maintainability
- Performance optimization and bundle size reduction
- Analytics and user behavior tracking
- Internationalization (i18n) for multiple languages

---

**Built with ❤️ using Angular 20 and modern web technologies**
