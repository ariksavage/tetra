import { ApplicationConfig, provideZoneChangeDetection, ErrorHandler } from '@angular/core';
import {Observable,of, from } from 'rxjs';
import { provideHttpClient, HttpClient } from '@angular/common/http';
import { provideRouter } from '@angular/router';
import { routes } from './app.routes';


const dependencies = [HttpClient];

export const appConfig: ApplicationConfig = {
  providers: [
    provideZoneChangeDetection({ eventCoalescing: true }),
    provideRouter(routes),
    provideHttpClient()
  ],
};
