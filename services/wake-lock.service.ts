import { Injectable, NgZone } from '@angular/core';
import { Observable, BehaviorSubject } from 'rxjs';

/**
 * Keeps the screen from dimming or locking, using the browser's Screen Wake Lock API.
 *
 * The browser releases the lock whenever the page is hidden, so it is requested
 * again each time the page becomes visible for as long as it is wanted.
 */
@Injectable({
  providedIn: 'root'
})
export class WakeLockService {
  /**
   * Whether the screen should be kept on
   */
  private wanted: boolean = false;
  private lock: any = null;
  private active = new BehaviorSubject<boolean>(false);
  private listening: boolean = false;

  constructor(private zone: NgZone) {}

  /**
   * True if this browser can keep the screen on.
   */
  supported(): boolean {
    return typeof navigator !== 'undefined' && 'wakeLock' in navigator;
  }

  /**
   * Whether the screen is currently being kept on.
   */
  isActive(): Observable<boolean> {
    return this.active.asObservable();
  }

  enable() {
    this.wanted = true;
    this.listen();
    return this.request();
  }

  disable() {
    this.wanted = false;
    const lock = this.lock;
    this.lock = null;
    this.active.next(false);
    return lock ? lock.release().catch(() => {}) : Promise.resolve();
  }

  private async request() {
    if (!this.wanted || !this.supported() || this.lock || document.visibilityState !== 'visible') {
      return;
    }
    try {
      const lock = await (navigator as any).wakeLock.request('screen');
      if (!this.wanted) {
        lock.release().catch(() => {});
        return;
      }
      this.lock = lock;
      this.zone.run(() => this.active.next(true));
      lock.addEventListener('release', () => {
        if (this.lock === lock) {
          this.lock = null;
          this.zone.run(() => this.active.next(false));
        }
      });
    } catch (e) {
      // Refused, eg. low power mode, or the browser wants an interaction first.
      this.active.next(false);
    }
  }

  /**
   * Ask again when the page comes back into view, or on the next interaction
   * if the browser refused a request made without one.
   */
  private listen() {
    if (this.listening || typeof document === 'undefined') {
      return;
    }
    this.listening = true;
    // Outside Angular: these fire on every press, and nothing on the page needs to
    // be re-checked because of them.
    this.zone.runOutsideAngular(() => {
      document.addEventListener('visibilitychange', () => this.request());
      document.addEventListener('pointerdown', () => this.request(), { passive: true });
    });
  }
}
