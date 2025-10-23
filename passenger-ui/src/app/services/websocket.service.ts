import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, Subject } from 'rxjs';

export interface Quote {
  id: string;
  departure: { lat: number; lon: number };
  destination: { lat: number; lon: number };
  distance: number;
  duration: number;
  fare: { amount: number; currency: string };
}

export interface RideStatus {
  status: 'idle' | 'requesting_quote' | 'quote_received' | 'booking_requested' | 'finding_driver' | 'driver_accepted' | 'ride_in_progress' | 'completed' | 'error';
  message?: string;
  quote?: Quote;
  rideId?: string;
}

export type ConnectionStatus = 'connecting' | 'connected' | 'disconnected' | 'error';

@Injectable({
  providedIn: 'root'
})
export class WebsocketService {
  private socket: WebSocket | null = null;
  private userId: string = '019a078d-e95e-7606-a2d8-b3dfa4bc1934'; // Default to Alice
  private messageSubject = new Subject<any>();
  private statusSubject = new BehaviorSubject<RideStatus>({ status: 'idle' });
  private connectionStatusSubject = new BehaviorSubject<ConnectionStatus>('connecting');

  public status$: Observable<RideStatus> = this.statusSubject.asObservable();
  public connectionStatus$: Observable<ConnectionStatus> = this.connectionStatusSubject.asObservable();

  constructor() {
    this.connect();
  }

  public setUserId(userId: string): void {
    this.userId = userId;
    this.reconnect();
  }

  public getUserId(): string {
    return this.userId;
  }

  public getConnectionStatus(): ConnectionStatus {
    return this.connectionStatusSubject.value;
  }

  private reconnect(): void {
    if (this.socket) {
      this.socket.close();
    }
    this.updateStatus({ status: 'idle' });
    this.connect();
  }

  private connect(): void {
    try {
      this.connectionStatusSubject.next('connecting');
      
      // Use the same host as the frontend for WebSocket connection
      // This allows it to work in dev containers, WSL2, and other environments
      const wsHost = window.location.hostname;
      const wsUrl = `ws://${wsHost}:8080`;
      
      console.log('Connecting to WebSocket:', wsUrl);
      this.socket = new WebSocket(wsUrl);

      this.socket.onopen = () => {
        console.log('WebSocket connected');
        this.connectionStatusSubject.next('connected');
        this.authenticate();
      };

      this.socket.onmessage = (event) => {
        const data = JSON.parse(event.data);
        console.log('Received message:', data);
        this.handleMessage(data);
      };

      this.socket.onerror = (error) => {
        console.error('WebSocket error:', error);
        this.connectionStatusSubject.next('error');
        this.updateStatus({ status: 'error', message: 'Connection error' });
      };

      this.socket.onclose = () => {
        console.log('WebSocket disconnected');
        this.connectionStatusSubject.next('disconnected');
        // Attempt to reconnect after 3 seconds
        setTimeout(() => this.connect(), 3000);
      };
    } catch (error) {
      console.error('Failed to connect:', error);
      this.connectionStatusSubject.next('error');
      this.updateStatus({ status: 'error', message: 'Failed to connect to server' });
    }
  }

  private authenticate(): void {
    this.send({
      type: 'auth_user',
      user_id: this.userId
    });
  }

  private send(data: any): void {
    if (this.socket && this.socket.readyState === WebSocket.OPEN) {
      this.socket.send(JSON.stringify(data));
    } else {
      console.error('WebSocket is not connected');
    }
  }

  private handleMessage(data: any): void {
    switch (data.type) {
      case 'auth_success':
        console.log('Authenticated as:', data.role);
        break;

      case 'quote_received':
        this.updateStatus({
          status: 'quote_received',
          quote: data.quote,
          message: `Quote received: $${data.quote.fare.amount} ${data.quote.fare.currency}`
        });
        break;

      case 'quote_error':
        this.updateStatus({
          status: 'error',
          message: data.error || 'Failed to get quote'
        });
        break;

      case 'ride_requested':
        this.updateStatus({
          status: 'finding_driver',
          rideId: data.ride_id,
          message: 'Finding a driver...'
        });
        break;

      case 'ride_accepted':
        this.updateStatus({
          status: 'driver_accepted',
          rideId: data.rideId,
          message: 'Driver is on the way to your pickup location!'
        });
        break;

      case 'ride_started':
        this.updateStatus({
          status: 'ride_in_progress',
          rideId: data.rideId,
          message: 'Enjoy your ride! Heading to destination...'
        });
        break;

      case 'ride_completed':
        this.updateStatus({
          status: 'completed',
          rideId: data.rideId,
          message: 'Ride completed! Thank you for using our service.'
        });
        break;

      case 'ride_rejected':
        this.updateStatus({
          status: 'finding_driver',
          message: 'Looking for another driver...'
        });
        break;

      case 'no_driver_available':
        this.updateStatus({
          status: 'error',
          message: data.message || 'No drivers available at the moment. Please try again later.',
          rideId: data.rideId
        });
        break;

      default:
        console.log('Unhandled message type:', data.type);
    }

    this.messageSubject.next(data);
  }

  private updateStatus(status: RideStatus): void {
    this.statusSubject.next(status);
  }

  public requestQuote(
    departureLat: number,
    departureLon: number,
    destinationLat: number,
    destinationLon: number
  ): void {
    this.updateStatus({ status: 'requesting_quote', message: 'Requesting quote...' });
    
    this.send({
      type: 'request_quote',
      user_id: this.userId,
      departure_lat: departureLat,
      departure_lon: departureLon,
      destination_lat: destinationLat,
      destination_lon: destinationLon
    });
  }

  public requestRide(
    departureLat: number,
    departureLon: number,
    destinationLat: number,
    destinationLon: number
  ): void {
    this.updateStatus({ status: 'booking_requested', message: 'Requesting ride...' });
    
    this.send({
      type: 'request_ride',
      user_id: this.userId,
      departure_lat: departureLat,
      departure_lon: departureLon,
      destination_lat: destinationLat,
      destination_lon: destinationLon
    });
  }

  public resetStatus(): void {
    this.updateStatus({ status: 'idle' });
  }
}
