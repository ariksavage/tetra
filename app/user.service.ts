import { Injectable } from '@angular/core';
import { CookiesService } from '@tetra/cookies.service';
import { CoreService } from './core.service';
import { Router, ActivatedRoute } from '@angular/router';
import { Observable, Subject, BehaviorSubject} from 'rxjs';
import { User } from '@tetra/user';

@Injectable({
  providedIn: 'root'
})
export class UserService {
  user = new Subject<User>();
  _user: User = new User();
  isAdmin: boolean = false;
  token: string = '';

  constructor(
    private core: CoreService,
    private route: Router,
    private cookies: CookiesService,
    protected activeRoute: ActivatedRoute
  ) {}

  getUser(): Observable<User> {
    const self = this;
    return this.user.asObservable();
    if (this._user){
      setTimeout(function(){
        self.setUser(self._user);
      }, 1000);
    }
  }

  setUser(user: User) {
    this._user = user;
    this.user.next(user);
  }

  loginByToken(token: string|null = null) {

    const self = this;
    if (token){
      this.token = token;
    } else {
      this.token = this.cookies.read('auth');
    }

    if (!this.token) {
      return new Promise((resolve, reject) => {resolve('no token found')});
    }
    this.core.setAuth(this.token);

    return this.core.get('users', 'current').then((data) => {
      if (data && data.user){
        const user = new User(data.user);
        self.setUser(user);
        return user;
      } else {
        self.token = '';
        const user = new User();
        self.setUser(user);
        self.cookies.delete('auth');
        return false;
      }
    });
  }

  loginRedirect() {
    const self = this;
    if (!(window.location.pathname.indexOf('/login') > -1)) {
      self.route.navigateByUrl('/login?redirect=' + window.location.pathname);
    }
  }

  login(username: string, password: string) {
    const self = this;
    const data = {username, password};
    return this.core.post('app', 'login', data).then((data)=> {
      if (data.token){
        self.cookies.set('auth', data.token, 100);
        const user = new User(data.user);
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
      const user = new User({});
      this.setUser(user);
      self.route.navigateByUrl('/logout');
    })
  }
}
