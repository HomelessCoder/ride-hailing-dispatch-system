import { Routes } from '@angular/router';
import { DriverComponent } from './driver/driver.component';

export const routes: Routes = [
  { path: '', redirectTo: '/driver/1', pathMatch: 'full' },
  { path: 'driver/:id', component: DriverComponent },
  { path: '**', redirectTo: '/driver/1' }
];
