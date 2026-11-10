import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { Route, Router, ActivatedRoute, NavigationEnd, NavigationStart } from '@angular/router';
import { CoreService } from './core.service';
import { Title } from "@angular/platform-browser";

@Injectable({
  providedIn: 'root'
})

export class ErrorService {
  private _error: string = '';
  private error = new BehaviorSubject<string>('');

  constructor() {}

  setError(error: string) {
    this._error = error;
    this.error.next(error);
  }

  getError() {
    return this.error.asObservable();
  }
}
