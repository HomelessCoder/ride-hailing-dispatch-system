import { CommonModule } from '@angular/common';
import { Component, OnDestroy, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Subscription } from 'rxjs';
import { ConnectionStatus, Quote, RideStatus, WebsocketService } from './services/websocket.service';

@Component({
  selector: 'app-root',
  imports: [CommonModule, FormsModule],
  templateUrl: './app.html',
  styleUrl: './app.css'
})
export class App implements OnInit, OnDestroy {
  // User data
  readonly ALICE_ID = '019a078d-e95e-7606-a2d8-b3dfa4bc1934';
  readonly BOB_ID = '019a078d-e95e-78de-9df5-9b4a39281169';
  currentUserId: string = this.ALICE_ID;
  currentUserName: string = 'Alice';
  
  // Location inputs - Default to London Downtown
  departureLat: number = 51.5073509;
  departureLon: number = -0.1277583;
  destinationLat: number = 51.4700223;
  destinationLon: number = -0.4542955;

  // State
  currentStatus: RideStatus = { status: 'idle' };
  currentQuote: Quote | null = null;
  statusMessage: string = '';
  connectionStatus: ConnectionStatus = 'connecting';
  
  // Track last quoted coordinates to detect changes
  private lastQuotedDepartureLat?: number;
  private lastQuotedDepartureLon?: number;
  private lastQuotedDestinationLat?: number;
  private lastQuotedDestinationLon?: number;
  
  private statusSubscription?: Subscription;
  private connectionStatusSubscription?: Subscription;

  constructor(private websocketService: WebsocketService) {}

  ngOnInit(): void {
    this.currentUserId = this.websocketService.getUserId();
    this.currentUserName = this.currentUserId === this.ALICE_ID ? 'Alice' : 'Bob';
    
    this.statusSubscription = this.websocketService.status$.subscribe(
      (status: RideStatus) => {
        this.currentStatus = status;
        this.statusMessage = status.message || '';
        
        if (status.quote) {
          this.currentQuote = status.quote;
        }
        
        // Reset quote if starting new booking flow
        if (status.status === 'idle') {
          this.currentQuote = null;
        }
      }
    );

    this.connectionStatusSubscription = this.websocketService.connectionStatus$.subscribe(
      (status: ConnectionStatus) => {
        this.connectionStatus = status;
      }
    );
  }

  ngOnDestroy(): void {
    this.statusSubscription?.unsubscribe();
    this.connectionStatusSubscription?.unsubscribe();
  }

  onRequestQuote(): void {
    // Save the coordinates for this quote request
    this.lastQuotedDepartureLat = this.departureLat;
    this.lastQuotedDepartureLon = this.departureLon;
    this.lastQuotedDestinationLat = this.destinationLat;
    this.lastQuotedDestinationLon = this.destinationLon;
    
    this.websocketService.requestQuote(
      this.departureLat,
      this.departureLon,
      this.destinationLat,
      this.destinationLon
    );
  }

  onRequestRide(): void {
    if (!this.currentQuote) {
      return;
    }
    
    this.websocketService.requestRide(
      this.departureLat,
      this.departureLon,
      this.destinationLat,
      this.destinationLon
    );
  }

  onStartOver(): void {
    this.currentQuote = null;
    this.lastQuotedDepartureLat = undefined;
    this.lastQuotedDepartureLon = undefined;
    this.lastQuotedDestinationLat = undefined;
    this.lastQuotedDestinationLon = undefined;
    this.websocketService.resetStatus();
  }

  get canRequestQuote(): boolean {
    // Can request quote if idle or error
    if (this.currentStatus.status === 'idle' || this.currentStatus.status === 'error') {
      return true;
    }
    
    // Can also request a new quote if we have a quote but coordinates have changed
    if (this.currentStatus.status === 'quote_received' && this.hasLocationChanged()) {
      return true;
    }
    
    return false;
  }

  private hasLocationChanged(): boolean {
    // Check if any coordinate has changed from the last quoted values
    return this.departureLat !== this.lastQuotedDepartureLat ||
           this.departureLon !== this.lastQuotedDepartureLon ||
           this.destinationLat !== this.lastQuotedDestinationLat ||
           this.destinationLon !== this.lastQuotedDestinationLon;
  }

  get canRequestRide(): boolean {
    return this.currentStatus.status === 'quote_received' && this.currentQuote !== null;
  }

  get isLoading(): boolean {
    return this.currentStatus.status === 'requesting_quote' || 
           this.currentStatus.status === 'booking_requested';
  }

  get showQuote(): boolean {
    return this.currentQuote !== null;
  }

  get isRideActive(): boolean {
    return this.currentStatus.status === 'finding_driver' || 
           this.currentStatus.status === 'driver_accepted' || 
           this.currentStatus.status === 'ride_in_progress';
  }

  get isCompleted(): boolean {
    return this.currentStatus.status === 'completed';
  }

  get hasError(): boolean {
    return this.currentStatus.status === 'error';
  }

  getStatusTitle(): string {
    switch (this.currentStatus.status) {
      case 'finding_driver':
        return 'Finding a Driver';
      case 'driver_accepted':
        return 'Driver On The Way!';
      case 'ride_in_progress':
        return 'Ride in Progress';
      case 'completed':
        return 'Ride Completed!';
      default:
        return 'Booking Status';
    }
  }

  getProgressWidth(): string {
    switch (this.currentStatus.status) {
      case 'finding_driver':
        return '25%';
      case 'driver_accepted':
        return '50%';
      case 'ride_in_progress':
        return '75%';
      case 'completed':
        return '100%';
      default:
        return '0%';
    }
  }

  // Quick location setters for London
  setLocationDowntown(isDeparture: boolean): void {
    if (isDeparture) {
      this.departureLat = 51.5073509;
      this.departureLon = -0.1277583;
    } else {
      this.destinationLat = 51.5073509;
      this.destinationLon = -0.1277583;
    }
  }

  setLocationHeathrow(isDeparture: boolean): void {
    if (isDeparture) {
      this.departureLat = 51.4700223;
      this.departureLon = -0.4542955;
    } else {
      this.destinationLat = 51.4700223;
      this.destinationLon = -0.4542955;
    }
  }

  setLocationMidtown(isDeparture: boolean): void {
    if (isDeparture) {
      this.departureLat = 51.5125;
      this.departureLon = -0.1357;
    } else {
      this.destinationLat = 51.5125;
      this.destinationLon = -0.1357;
    }
  }

  setLocationUptown(isDeparture: boolean): void {
    if (isDeparture) {
      this.departureLat = 51.515419;
      this.departureLon = -0.141588;
    } else {
      this.destinationLat = 51.515419;
      this.destinationLon = -0.141588;
    }
  }

  // Helper methods to check if current coordinates match predefined locations
  private coordinatesMatch(lat1: number, lon1: number, lat2: number, lon2: number): boolean {
    // Compare with reasonable precision (4 decimal places ~11 meters)
    return Math.abs(lat1 - lat2) < 0.0001 && Math.abs(lon1 - lon2) < 0.0001;
  }

  isDowntownSelected(isDeparture: boolean): boolean {
    const lat = isDeparture ? this.departureLat : this.destinationLat;
    const lon = isDeparture ? this.departureLon : this.destinationLon;
    return this.coordinatesMatch(lat, lon, 51.5073509, -0.1277583);
  }

  isHeathrowSelected(isDeparture: boolean): boolean {
    const lat = isDeparture ? this.departureLat : this.destinationLat;
    const lon = isDeparture ? this.departureLon : this.destinationLon;
    return this.coordinatesMatch(lat, lon, 51.4700223, -0.4542955);
  }

  isMidtownSelected(isDeparture: boolean): boolean {
    const lat = isDeparture ? this.departureLat : this.destinationLat;
    const lon = isDeparture ? this.departureLon : this.destinationLon;
    return this.coordinatesMatch(lat, lon, 51.5125, -0.1357);
  }

  isUptownSelected(isDeparture: boolean): boolean {
    const lat = isDeparture ? this.departureLat : this.destinationLat;
    const lon = isDeparture ? this.departureLon : this.destinationLon;
    return this.coordinatesMatch(lat, lon, 51.515419, -0.141588);
  }

  // User authorization methods
  switchToAlice(): void {
    this.currentUserId = this.ALICE_ID;
    this.currentUserName = 'Alice';
    this.websocketService.setUserId(this.ALICE_ID);
    this.onStartOver();
  }

  switchToBob(): void {
    this.currentUserId = this.BOB_ID;
    this.currentUserName = 'Bob';
    this.websocketService.setUserId(this.BOB_ID);
    this.onStartOver();
  }

  get isAlice(): boolean {
    return this.currentUserId === this.ALICE_ID;
  }

  get isBob(): boolean {
    return this.currentUserId === this.BOB_ID;
  }

  // Connection status display methods
  get connectionStatusText(): string {
    switch (this.connectionStatus) {
      case 'connecting':
        return 'Connecting to WebSocket Server...';
      case 'connected':
        return 'Connected to WebSocket Server on port 8080';
      case 'disconnected':
        return 'Disconnected from WebSocket Server (reconnecting...)';
      case 'error':
        return 'WebSocket Connection Error (retrying...)';
      default:
        return 'WebSocket Status Unknown';
    }
  }

  get connectionStatusClass(): string {
    switch (this.connectionStatus) {
      case 'connected':
        return 'status-connected';
      case 'connecting':
        return 'status-connecting';
      case 'disconnected':
      case 'error':
        return 'status-disconnected';
      default:
        return '';
    }
  }

  get connectionStatusIcon(): string {
    switch (this.connectionStatus) {
      case 'connected':
        return '🟢';
      case 'connecting':
        return '🟡';
      case 'disconnected':
      case 'error':
        return '🔴';
      default:
        return '⚪';
    }
  }
}
