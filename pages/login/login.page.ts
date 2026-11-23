import { Component, ChangeDetectionStrategy, inject } from '@angular/core';
import { RouterLink } from '@angular/router';

import { FieldTextComponent } from '@tetra/components/field-text/field-text.component';
import { FieldPasswordComponent } from '@tetra/components/field-password/field-password.component';
import { Page } from '@tetra/pages/page/page.component';
import { ConnectionService } from '@tetra/services/connection.service';

@Component({
    selector: 'login-page',
    imports: [FieldTextComponent, FieldPasswordComponent, RouterLink],
    templateUrl: './login.page.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrls: ['./login.page.scss']
})

export class LoginPageComponent extends Page {
  public override title = 'Login';
  protected override requiresLogin = false;
  username: string = '';
  password: string = '';
  /** eg. after setting a new password: "Your new password is set." */
  notice: string = '';
  loginError: string = '';
  private connection = inject(ConnectionService);

  loginSubmit() {
    const self = this;
    if (!this.username || !this.password) {
      return;
    }
    this.loginError = '';
    this.userService.login(this.username, this.password).then((user: any) => {
      if (user) {
        self.goHome();
      } else {
        // A login that never reached the server isn't a wrong password.
        self.loginError = {
          unreachable: "Couldn't log in: the server can't be reached.",
          database: "Couldn't log in: the database isn't available.",
        }[self.connection.problem() as string] ?? 'Login failed. Please check your username and password.';
      }
    })
  }

  override load() {
    if (this.currentUser && this.currentUser.id){
      this.goHome();
    }
    // Back from setting a new password: /login?username=arik&reset=1
    const params = this.activeRoute.snapshot.queryParamMap;
    this.username = params.get('username') ?? this.username;
    if (params.get('reset')) {
      this.notice = 'Your new password is set. Log in with it.';
    }
  }

  goHome() {
    // Return to the page that asked for login, if it is a path on this site.
    const redirect = this.activeRoute.snapshot.queryParamMap.get('redirect') || '';
    if (redirect.startsWith('/') && !redirect.startsWith('//') && !redirect.startsWith('/login')) {
      this.route.navigateByUrl(redirect);
      return;
    }
    // Otherwise the site root decides where a logged in user lands.
    this.route.navigateByUrl('/');
  }
}
