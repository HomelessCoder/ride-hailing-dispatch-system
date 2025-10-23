import { CommonModule } from '@angular/common';
import { Component, OnDestroy, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { Subscription } from 'rxjs';
import {
    ConnectionStatus,
    DriverState,
    RideRequest,
    WebsocketService
} from '../services/websocket.service';

// Predefined driver IDs and names from database
const DRIVER_IDS: { [key: string]: string } = {
  '1': '019a078d-e95e-75c1-ac7e-6121da5520ed', // Eve
  '2': '019a078d-e95e-7981-914e-b5104cd166ee', // Charlie
  '3': '019a078d-e95e-7dba-be51-b1e4d16ff8a8', // Frank
  '4': '019a078d-e95e-7e9f-8de1-e6d25c4dcbd4'  // David
};

const DRIVER_NAMES: { [key: string]: string } = {
  '1': 'Eve',
  '2': 'Charlie',
  '3': 'Frank',
  '4': 'David'
};

// Predefined London locations for quick selection
interface LocationPreset {
  name: string;
  lat: number;
  lon: number;
}

const LOCATION_PRESETS: LocationPreset[] = [
  { name: 'Downtown London', lat: 51.5073509, lon: -0.1277583 },
  { name: 'Heathrow Airport', lat: 51.4700223, lon: -0.4542955 },
  { name: 'King\'s Cross', lat: 51.5301, lon: -0.1232 },
  { name: 'Canary Wharf', lat: 51.5054, lon: -0.0235 },
  { name: 'Westminster', lat: 51.4994, lon: -0.1245 }
];

@Component({
  selector: 'app-driver',
  imports: [CommonModule, FormsModule],
  templateUrl: './driver.component.html',
  styleUrls: ['./driver.component.css']
})
export class DriverComponent implements OnInit, OnDestroy {
  driverNumber: string = '1';
  driverId: string = '';
  driverName: string = '';
  
  // Location
  currentLat: number = 51.5073509; // Default to Downtown London
  currentLon: number = -0.1277583;
  locationPresets = LOCATION_PRESETS;
  
  // State
  driverState: DriverState = { status: 'offline' };
  connectionStatus: ConnectionStatus = 'disconnected';
  currentRideRequest: RideRequest | null = null;
  statusUpdateFeedback: { type: 'success' | 'error', message: string } | null = null;
  isUpdatingStatus: boolean = false;
  
  private stateSubscription?: Subscription;
  private connectionSubscription?: Subscription;
  private rideRequestSubscription?: Subscription;
  private routeSubscription?: Subscription;
  private statusUpdateFeedbackSubscription?: Subscription;

  constructor(
    private websocketService: WebsocketService,
    private route: ActivatedRoute
  ) {}

  ngOnInit(): void {
    // Get driver ID from route
    this.routeSubscription = this.route.params.subscribe((params) => {
      this.driverNumber = params['id'] || '1';
      this.driverId = DRIVER_IDS[this.driverNumber] || DRIVER_IDS['1'];
      this.driverName = DRIVER_NAMES[this.driverNumber] || 'Driver ' + this.driverNumber;

      // Connect to WebSocket
      this.websocketService.connect(this.driverId);
    });

    // Subscribe to driver state
    this.stateSubscription = this.websocketService.driverState$.subscribe(
      (state: DriverState) => {
        this.driverState = state;
        
        // Update UI location when state location changes
        if (state.location) {
          this.currentLat = state.location.lat;
          this.currentLon = state.location.lon;
        }
      }
    );

    // Subscribe to connection status
    this.connectionSubscription = this.websocketService.connectionStatus$.subscribe(
      (status: ConnectionStatus) => {
        this.connectionStatus = status;
      }
    );

    // Subscribe to ride requests
    this.rideRequestSubscription = this.websocketService.rideRequest$.subscribe(
      (request: RideRequest | null) => {
        this.currentRideRequest = request;
      }
    );

    // Subscribe to status update feedback
    this.statusUpdateFeedbackSubscription = this.websocketService.statusUpdateFeedback$.subscribe(
      (feedback) => {
        this.statusUpdateFeedback = feedback;
        if (feedback) {
          this.isUpdatingStatus = false;
        }
      }
    );
  }

  ngOnDestroy(): void {
    this.stateSubscription?.unsubscribe();
    this.connectionSubscription?.unsubscribe();
    this.rideRequestSubscription?.unsubscribe();
    this.routeSubscription?.unsubscribe();
    this.statusUpdateFeedbackSubscription?.unsubscribe();
    this.websocketService.disconnect();
  }

  onUpdateLocation(): void {
    this.websocketService.updateLocation(this.currentLat, this.currentLon);
  }

  onSetLocation(preset: LocationPreset): void {
    this.currentLat = preset.lat;
    this.currentLon = preset.lon;
    this.onUpdateLocation();
  }

  onAcceptRide(): void {
    if (this.currentRideRequest) {
      this.websocketService.acceptRide(this.currentRideRequest.ride_id);
    }
  }

  onRejectRide(): void {
    if (this.currentRideRequest) {
      this.websocketService.rejectRide(this.currentRideRequest.ride_id);
    }
  }

  onStartRide(): void {
    this.websocketService.startRide();
  }

  onEndRide(): void {
    this.websocketService.endRide();
  }

  onStatusChange(newStatus: 'available' | 'busy' | 'offline'): void {
    // Prevent status change if already updating or not connected
    if (this.isUpdatingStatus || !this.isConnected) {
      return;
    }

    // Prevent going offline while on an active ride
    if (newStatus === 'offline' && this.isBusy) {
      this.statusUpdateFeedback = {
        type: 'error',
        message: 'Cannot go offline while on an active ride'
      };
      setTimeout(() => this.statusUpdateFeedback = null, 3000);
      return;
    }

    // Prevent manually setting to busy (should happen through ride acceptance)
    if (newStatus === 'busy' && !this.isBusy) {
      this.statusUpdateFeedback = {
        type: 'error',
        message: 'Status is automatically set to busy when you accept a ride'
      };
      setTimeout(() => this.statusUpdateFeedback = null, 3000);
      return;
    }

    this.isUpdatingStatus = true;
    this.websocketService.updateStatus(newStatus);
  }

  get isConnected(): boolean {
    return this.connectionStatus === 'connected';
  }

  get isAvailable(): boolean {
    return this.driverState.status === 'available';
  }

  get isBusy(): boolean {
    return this.driverState.status === 'busy';
  }

  get isHeadingToPickup(): boolean {
    return this.isBusy && this.driverState.rideStatus === 'heading_to_pickup';
  }

  get isPassengerOnBoard(): boolean {
    return this.isBusy && this.driverState.rideStatus === 'passenger_on_board';
  }

  get hasRideRequest(): boolean {
    return this.currentRideRequest !== null;
  }

  get statusClass(): string {
    switch (this.driverState.status) {
      case 'available':
        return 'status-available';
      case 'busy':
        return 'status-busy';
      case 'offline':
        return 'status-offline';
      default:
        return '';
    }
  }

  get connectionClass(): string {
    switch (this.connectionStatus) {
      case 'connected':
        return 'connection-connected';
      case 'connecting':
        return 'connection-connecting';
      case 'error':
        return 'connection-error';
      case 'disconnected':
        return 'connection-disconnected';
      default:
        return '';
    }
  }

  formatLocation(lat: number, lon: number): string {
    return `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
  }

  formatDistance(km?: number): string {
    if (!km) return 'N/A';
    return `${km.toFixed(1)} km`;
  }

  formatDuration(minutes?: number): string {
    if (!minutes) return 'N/A';
    return `${Math.round(minutes)} min`;
  }

  isCurrentLocation(preset: LocationPreset): boolean {
    // Compare with a small tolerance for floating point precision
    const tolerance = 0.0001;
    return Math.abs(this.currentLat - preset.lat) < tolerance &&
           Math.abs(this.currentLon - preset.lon) < tolerance;
  }

  formatFare(fare?: { amount: number; currency: string }): string {
    if (!fare) return 'N/A';
    return `${fare.currency} ${fare.amount.toFixed(2)}`;
  }
}
