import { Injectable } from '@angular/core';
import { Observable, BehaviorSubject } from 'rxjs';
import { CookiesService } from '@tetra/services/cookies.service';

export type ConsentStatus = 'accepted' | 'declined' | null;

/**
 * Cookie consent (opt-in).
 *
 * Essential cookies, such as the login session and the consent choice itself,
 * are always allowed. Optional cookies may only be set once the visitor accepts.
 * Until they answer, they are treated as having declined.
 */
@Injectable({
  providedIn: 'root'
})
export class ConsentService {
  /**
   * Name of the cookie that remembers the visitor's choice
   */
  private cookieName: string = 'cookie_consent';

  /**
   * Days before the visitor is asked again
   */
  private days: number = 180;

  private _optionalCookies: Array<string> = [];

  /**
   * Names of the optional cookies this app sets.
   * They are removed unless the visitor has accepted: not answering
   * is treated the same as declining.
   */
  get optionalCookies(): Array<string> {
    return this._optionalCookies;
  }

  set optionalCookies(names: Array<string>) {
    this._optionalCookies = names;
    this.enforce();
  }

  private open = new BehaviorSubject<boolean>(false);

  constructor(private cookies: CookiesService) {
    this.open.next(this.status() === null);
  }

  /**
   * The visitor's choice, or null if they have not been asked yet.
   */
  status(): ConsentStatus {
    const value = this.cookies.read(this.cookieName);
    return value === 'accepted' || value === 'declined' ? value : null;
  }

  /**
   * True if optional cookies may be set.
   */
  allowed(): boolean {
    return this.status() === 'accepted';
  }

  accept() {
    this.cookies.set(this.cookieName, 'accepted', this.days);
    this.open.next(false);
  }

  decline() {
    this.cookies.set(this.cookieName, 'declined', this.days);
    this.enforce();
    this.open.next(false);
  }

  /**
   * Remove optional cookies unless the visitor has accepted them.
   */
  enforce() {
    if (!this.allowed()) {
      this._optionalCookies.forEach((name: string) => {
        if (this.cookies.read(name)) {
          this.cookies.delete(name);
        }
      });
    }
  }

  /**
   * Set an optional cookie, only if the visitor has accepted.
   * @return boolean True if the cookie was set
   */
  setOptional(name: string, value: any, days: number = 30): boolean {
    if (!this.allowed()) {
      return false;
    }
    this.cookies.set(name, value, days);
    return true;
  }

  /**
   * Show the consent banner again so the visitor can change their choice.
   */
  review() {
    this.open.next(true);
  }

  /**
   * Whether the consent banner should be showing.
   */
  isOpen(): Observable<boolean> {
    return this.open.asObservable();
  }
}
