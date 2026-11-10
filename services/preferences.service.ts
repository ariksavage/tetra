import { Injectable } from '@angular/core';
import { Observable, BehaviorSubject } from 'rxjs';
import { CoreService } from '@tetra/services/core.service';
import { UserService } from '@tetra/services/user.service';
import { User } from '@tetra/classes/user';

/**
 * User preferences: small key / value settings such as UI options,
 * and later things like dietary preferences or allergies.
 *
 * - Logged in: stored on the server against the user, so they follow the account.
 * - Anonymous: stored in this browser only.
 *
 * The browser copy is also used as a cache, so a preference can be applied
 * as soon as the page loads, before the server has answered.
 */
@Injectable({
  providedIn: 'root'
})
export class PreferencesService {
  private storageKey: string = 'preferences';
  private values: { [key: string]: any } = {};
  private preferences = new BehaviorSubject<{ [key: string]: any }>({});
  private userId: number = 0;

  constructor(
    private core: CoreService,
    private userService: UserService
  ) {
    this.values = this.readLocal();
    this.preferences.next(this.values);

    // Load the account's preferences once the user is known
    this.userService.getUser()?.subscribe((user: User | null) => {
      const id = user && user.id ? Number(user.id) : 0;
      if (id !== this.userId) {
        const loggedOut = this.userId && !id;
        this.userId = id;
        if (id) {
          this.fetch();
        } else if (loggedOut) {
          // Don't leave one person's preferences behind in a shared browser.
          this.apply({});
        }
      }
    });
  }

  /**
   * All preferences, emitted again whenever one changes.
   */
  all(): Observable<{ [key: string]: any }> {
    return this.preferences.asObservable();
  }

  /**
   * Get one preference.
   * @param key      Preference name: lowercase letters, numbers and underscores
   * @param fallback Value to use if the preference has not been set
   */
  get(key: string, fallback: any = null): any {
    return key in this.values ? this.values[key] : fallback;
  }

  /**
   * Save one preference.
   */
  set(key: string, value: any) {
    this.apply({ ...this.values, [key]: value });
    if (this.userId) {
      return this.core.post('users', 'preferences', { preferences: { [key]: value } }, false);
    }
    return Promise.resolve(true);
  }

  /**
   * Load the logged in user's preferences from the server.
   * The server's values win over what this browser had cached.
   */
  private fetch() {
    return this.core.get('users', 'preferences').then((data: any) => {
      if (data && data.preferences) {
        this.apply({ ...this.values, ...data.preferences });
      }
    });
  }

  private apply(values: { [key: string]: any }) {
    this.values = values;
    this.writeLocal(values);
    this.preferences.next(values);
  }

  private readLocal(): { [key: string]: any } {
    try {
      const stored = JSON.parse(window.localStorage.getItem(this.storageKey) || '{}');
      return stored && typeof stored === 'object' ? stored : {};
    } catch (e) {
      return {};
    }
  }

  private writeLocal(values: { [key: string]: any }) {
    try {
      window.localStorage.setItem(this.storageKey, JSON.stringify(values));
    } catch (e) {
      // Storage unavailable (eg. private browsing): preferences last for this visit only.
    }
  }
}
