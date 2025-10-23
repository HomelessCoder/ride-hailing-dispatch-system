import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';

export interface RideRequest {
  ride_id: string;
  user_id: string;
  departure: { lat: number; lon: number };
  destination: { lat: number; lon: number };
  distance?: number;
  duration?: number;
  fare?: { amount: number; currency: string };
}

export interface DriverLocation {
  lat: number;
  lon: number;
}

export type DriverStatus = 'available' | 'busy' | 'offline';
export type ConnectionStatus = 'connecting' | 'connected' | 'disconnected' | 'error';
export type RideStatus = 'heading_to_pickup' | 'passenger_on_board';

export interface DriverState {
  status: DriverStatus;
  currentRide?: RideRequest;
  location?: DriverLocation;
  rideStatus?: RideStatus;
}

@Injectable({
  providedIn: 'root'
})
export class WebsocketService {
  private socket: WebSocket | null = null;
  private driverId: string = '';
  
  private driverStateSubject = new BehaviorSubject<DriverState>({ 
    status: 'offline' 
  });
  
  private connectionStatusSubject = new BehaviorSubject<ConnectionStatus>('disconnected');
  private rideRequestSubject = new BehaviorSubject<RideRequest | null>(null);
  private statusUpdateFeedbackSubject = new BehaviorSubject<{ type: 'success' | 'error', message: string } | null>(null);

  public driverState$: Observable<DriverState> = this.driverStateSubject.asObservable();
  public connectionStatus$: Observable<ConnectionStatus> = this.connectionStatusSubject.asObservable();
  public rideRequest$: Observable<RideRequest | null> = this.rideRequestSubject.asObservable();
  public statusUpdateFeedback$: Observable<{ type: 'success' | 'error', message: string } | null> = this.statusUpdateFeedbackSubject.asObservable();

  constructor() {}

  public connect(driverId: string): void {
    this.driverId = driverId;
    this.connectWebSocket();
  }

  public disconnect(): void {
    if (this.socket) {
      this.socket.close();
      this.socket = null;
    }
    this.updateDriverState({ status: 'offline' });
  }

  public getDriverId(): string {
    return this.driverId;
  }

  public getCurrentState(): DriverState {
    return this.driverStateSubject.value;
  }

  public updateLocation(lat: number, lon: number): void {
    const currentState = this.driverStateSubject.value;
    this.updateDriverState({
      ...currentState,
      location: { lat, lon }
    });

    // Send location update to backend
    this.send({
      type: 'update_location',
      driver_id: this.driverId,
      lat: lat,
      lon: lon
    });
  }

  public acceptRide(rideId: string): void {
    this.send({
      type: 'accept_ride',
      ride_id: rideId,
      driver_id: this.driverId
    });

    const currentRide = this.rideRequestSubject.value;
    this.updateDriverState({ 
      status: 'busy',
      currentRide: currentRide || undefined,
      rideStatus: 'heading_to_pickup'
    });
    this.rideRequestSubject.next(null);
  }

  public rejectRide(rideId: string): void {
    this.send({
      type: 'reject_ride',
      ride_id: rideId,
      driver_id: this.driverId
    });

    this.rideRequestSubject.next(null);
  }

  public startRide(): void {
    const currentState = this.driverStateSubject.value;
    if (currentState.currentRide) {
      // Notify backend that ride is starting (passenger picked up)
      this.send({
        type: 'start_ride',
        ride_id: currentState.currentRide.ride_id,
        driver_id: this.driverId
      });

      // Update local state to passenger on board
      this.updateDriverState({
        ...currentState,
        rideStatus: 'passenger_on_board'
      });
    }
  }

  public endRide(): void {
    const currentState = this.driverStateSubject.value;
    if (currentState.currentRide) {
      // Notify backend that ride is completed
      this.send({
        type: 'complete_ride',
        ride_id: currentState.currentRide.ride_id,
        driver_id: this.driverId
      });
    }

    this.updateDriverState({ 
      status: 'available',
      location: currentState.location
    });
  }

  public updateStatus(status: DriverStatus): void {
    this.send({
      type: 'update_status',
      driver_id: this.driverId,
      status: status
    });
  }

  private connectWebSocket(): void {
    try {
      this.connectionStatusSubject.next('connecting');
      
      // Use the same host as the frontend for WebSocket connection
      const wsHost = window.location.hostname;
      const wsUrl = `ws://${wsHost}:8080`;
      
      console.log('Driver connecting to WebSocket:', wsUrl);
      this.socket = new WebSocket(wsUrl);

      this.socket.onopen = () => {
        console.log('Driver WebSocket connected');
        this.connectionStatusSubject.next('connected');
        this.authenticate();
      };

      this.socket.onmessage = (event) => {
        const data = JSON.parse(event.data);
        console.log('Driver received message:', data);
        this.handleMessage(data);
      };

      this.socket.onerror = (error) => {
        console.error('Driver WebSocket error:', error);
        this.connectionStatusSubject.next('error');
      };

      this.socket.onclose = () => {
        console.log('Driver WebSocket disconnected');
        this.connectionStatusSubject.next('disconnected');
        // Attempt to reconnect after 3 seconds
        setTimeout(() => {
          if (this.driverId) {
            this.connectWebSocket();
          }
        }, 3000);
      };
    } catch (error) {
      console.error('Failed to connect:', error);
      this.connectionStatusSubject.next('error');
    }
  }

  private authenticate(): void {
    this.send({
      type: 'auth_driver',
      driver_id: this.driverId
    });
  }

  private send(data: any): void {
    if (this.socket && this.socket.readyState === WebSocket.OPEN) {
      this.socket.send(JSON.stringify(data));
      console.log('Driver sent message:', data);
    } else {
      console.error('WebSocket is not connected');
    }
  }

  private handleMessage(data: any): void {
    switch (data.type) {
      case 'auth_success':
        console.log('Driver authenticated as:', data.role);
        const initialState: DriverState = { 
          status: data.status || 'available' // Restore status from backend or default to available
        };
        
        // Restore last known location if provided by backend
        if (data.current_location) {
          initialState.location = {
            lat: data.current_location.lat,
            lon: data.current_location.lon
          };
          console.log('Restored driver location:', initialState.location);
        }
        
        console.log('Restored driver status:', initialState.status);
        this.updateDriverState(initialState);
        break;

      case 'ride_request':
        console.log('Received ride request:', data);
        this.handleRideRequest(data);
        break;

      case 'ride_cancelled':
        console.log('Ride was cancelled:', data);
        this.rideRequestSubject.next(null);
        break;

      case 'driver_ride_request_timeout':
        console.log('Ride request timeout - driver did not respond in time:', data);
        this.rideRequestSubject.next(null);
        break;

      case 'driver_found':
        console.log('Driver found for ride:', data);
        // This message indicates a driver was matched to a ride
        // For the driver app, this means we should show the ride request
        if (data.driverId === this.driverId) {
          this.handleRideRequest({
            type: 'ride_request',
            ride_id: data.rideId,
            user_id: data.userId || 'unknown',
            departure_lat: data.departureLocation?.latitude,
            departure_lon: data.departureLocation?.longitude,
            destination_lat: data.destinationLocation?.latitude,
            destination_lon: data.destinationLocation?.longitude
          });
        }
        break;

      case 'ride_accepted':
        console.log('Ride accepted confirmed by backend');
        break;

      case 'ride_started':
        console.log('Ride started confirmed by backend');
        const currentState = this.driverStateSubject.value;
        this.updateDriverState({
          ...currentState,
          rideStatus: 'passenger_on_board'
        });
        break;

      case 'ride_completed':
        console.log('Ride completed confirmed by backend');
        this.updateDriverState({
          status: 'available',
          location: this.driverStateSubject.value.location
        });
        break;

      case 'ride_rejected':
        console.log('Ride rejected by driver');
        break;

      case 'no_drivers_available':
        console.log('No drivers available event received');
        break;

      case 'location_update_queued':
        console.log('Location update queued by backend');
        break;

      case 'status_update_queued':
        console.log('Status update queued:', data);
        // Update local state to reflect the new status
        const stateAfterUpdate = this.driverStateSubject.value;
        this.updateDriverState({
          ...stateAfterUpdate,
          status: data.status
        });
        this.statusUpdateFeedbackSubject.next({
          type: 'success',
          message: `Status updated to ${data.status}`
        });
        // Clear feedback after 3 seconds
        setTimeout(() => this.statusUpdateFeedbackSubject.next(null), 3000);
        break;

      case 'status_update_error':
        console.error('Status update error:', data.error);
        this.statusUpdateFeedbackSubject.next({
          type: 'error',
          message: data.error || 'Failed to update status'
        });
        // Clear feedback after 5 seconds for errors
        setTimeout(() => this.statusUpdateFeedbackSubject.next(null), 5000);
        break;

      default:
        console.log('Unhandled message type:', data.type);
    }
  }

  private handleRideRequest(data: any): void {
    const rideRequest: RideRequest = {
      ride_id: data.ride_id,
      user_id: data.user_id,
      departure: {
        lat: data.departure_lat || data.departure?.lat,
        lon: data.departure_lon || data.departure?.lon
      },
      destination: {
        lat: data.destination_lat || data.destination?.lat,
        lon: data.destination_lon || data.destination?.lon
      },
      distance: data.distance,
      duration: data.duration,
      fare: data.fare
    };

    this.rideRequestSubject.next(rideRequest);
  }

  private updateDriverState(state: DriverState): void {
    this.driverStateSubject.next(state);
  }
}
