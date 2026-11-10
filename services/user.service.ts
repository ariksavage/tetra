import { Injectable, Inject, PLATFORM_ID, DOCUMENT } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { Observable, Subject, BehaviorSubject, ReplaySubject } from 'rxjs';
import { isPlatformBrowser } from '@angular/common';
import {  CookiesService  } from '@tetra/services/cookies.service';
import { CoreService } from '@tetra/services/core.service';
import { User } from '@tetra/classes/user';


@Injectable({
  providedIn: 'root'
})
export class UserService {
  // Replays the current user, so parts of the page created later (eg. the user menu
  // on a page reached by in-app navigation) still learn who is logged in.
  user = new ReplaySubject<User>(1);
  _user: User = new User();
  isAdmin: boolean = false;
  token: string = '';
  /**
   * The session token the current user was loaded with
   */
  private loadedToken: string = '';
  passwordRules = [
    {
      label: 'Password must be at least 8 characters long',
      fn: (password: string) => password.length >= 8
    },
    {
      label: 'Password must contain one lowercase letter',
      fn: (password: string) => /[a-z]/.test(password)
    },
    {
      label: 'Password must contain one uppercase letter',
      fn: (password: string) => /[A-Z]/.test(password)
    },
    {
      label: 'Password must contain one number',
      fn: (password: string) => /[0-9]/.test(password)
    }
  ];

  constructor(
    private core: CoreService,
    private route: Router,
    private cookies: CookiesService,
    protected activeRoute: ActivatedRoute,
    @Inject(PLATFORM_ID) private platformId: Object,
    @Inject(DOCUMENT) private document: Document
  ) {}

  validatePassword(password: string) {
    let valid = true;
    this.passwordRules.forEach((rule: any) => {
      if (!rule.fn(password)) {
        valid = false;
      }
    });
    return valid;
  }

  getUser(): Observable<User> {
    const self = this;
    return this.user.asObservable();
  }

  setUser(user: User) {
    this._user = new User(user);
    this.user.next(this._user);
  }

  loginByToken(token: string|null = null) {
    const self = this;
    if (token){
      this.token = token;
      self.cookies.delete('auth');
    } else {
      this.token = this.cookies.read('auth');
    }


    if (!this.token) {
      return new Promise((resolve, reject) => {resolve('no token found')});
    }

    // Already loaded for this session token: no need to ask the server again on every page.
    if (this._user && this._user.id && this.token === this.loadedToken) {
      return Promise.resolve(this._user);
    }

    this.core.setAuth(this.token);
    const requestedToken = this.token;

    return this.core.get('users', 'current').then((data) => {
      if (data && data.user){
        const user = new User(data.user);
        self.loadedToken = requestedToken;
        self.setUser(user);
        return user;
      } else {
        self.loadedToken = '';
        self.token = '';
        const user = new User();
        self.setUser(user);
        self.deleteAuthCookie()
        return false;
      }
    });
  }

  deleteAuthCookie() {
    this.cookies.delete('auth');
  }

  loginRedirect() {
    const self = this;
    if (isPlatformBrowser(this.platformId) && !(window.location.pathname.indexOf('/login') > -1)) {
      self.route.navigateByUrl('/login?redirect=' + window.location.pathname);
    }
  }

  login(username: string, password: string) {
    const self = this;
    const data = {username, password};
    // Stay on the login form when credentials are rejected.
    return this.core.post('app', 'login', data, false).then((data)=> {
      if (data.token){
        self.cookies.set('auth', data.token, 100);
        const user = new User(data.user);
        self.token = data.token;
        self.loadedToken = '';
        self.core.setAuth(data.token);
        self.setUser(user);
        return data.user;
      } else {
        return false;
      }
    })
  }

  logout() {
    const self = this;
    return this.core.get('users', 'logout').then((data)=> {
      self.cookies.delete('auth');
      self.token = '';
      self.loadedToken = '';
      self.core.setAuth('');
      const user = new User({});
      this.setUser(user);
      self.route.navigateByUrl('/');
    })
  }

  forgotPassword(username: string) {
    const host = window.location.origin;
    const params = {
      username,
      host
    };
    return this.core.getParams(params, 'users', 'password-reset');
  }

  resetPassword(newPassword: string, repeatNewPassword: string) {
    const payload = {
      newPassword,
      repeatNewPassword
    };
    return this.core.post('users', 'password-reset', payload);
  }
}
