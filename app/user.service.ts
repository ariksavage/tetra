import { Injectable } from '@angular/core';
import { CookiesService } from './cookies.service';
import { CoreService } from './core.service';
import { Router, ActivatedRoute } from '@angular/router';
import { Observable, Subject, BehaviorSubject} from 'rxjs';
import { User } from './user';

@Injectable({
  providedIn: 'root'
})
export class UserService {
  current = new Subject<User>();
  user: User = new User({});
  isAdmin: boolean = false;
  token: string = '';

  constructor(
    private core: CoreService,
    private route: Router,
    private cookies: CookiesService,
    protected activeRoute: ActivatedRoute
  ) {}

  getUser(): Observable<User> {
    return this.current.asObservable();
  }

  requireLogin() {
    const self = this;

    return this.loginByToken();
  }

  setUser(user: User) {
    this.current.next(user);
    this.user = user;
  }

  loginByToken(token: string|null = null) {
    const self = this;
    if (token){
      this.token = token;
    } else {
      this.token = this.cookies.read('auth');
    }

    if (!this.token) {
      if (window.location.pathname != '/login') {
        self.loginRedirect()
      }
      return new Promise((resolve, reject) => {});
    }
    this.core.setAuth(this.token);

    return this.core.get('user', 'current').then((data) => {
      if (data && data.user){
        const user = new User(data.user);
        this.setUser(user);
        return user;
      } else {
        self.token = '';
        const user = new User({});
        this.setUser(user);
        self.cookies.delete('auth');
        self.loginRedirect();
        return false;
      }
    });
  }
  loginRedirect(){
    const self = this;
    self.route.navigateByUrl('/login?redirect=' + window.location.pathname);
  }

  login(username: string, password: string) {
    const self = this;
    return this.core.post('user', 'login', {username, password}).then((data)=> {
      if (data.authorization_token){
        self.cookies.set('auth', data.authorization_token, 1);
        const user = new User(data.user);
        self.setUser(user);
        if (this.user.hasRole('administrator')) {
          this.isAdmin = true;
        }
        return data.user;
      } else {
        return false;
      }
    })
  }

  logout() {
    const self = this;
    return this.core.get('user', 'logout').then((data)=> {
      self.cookies.delete('auth');
      const user = new User({});
      this.setUser(user);
      return true;
    })
  }
}
