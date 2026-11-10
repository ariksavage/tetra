import { Component, ChangeDetectionStrategy } from '@angular/core';

import { FieldTextComponent } from '@tetra/components/field-text/field-text.component';
import { FieldPasswordComponent } from '@tetra/components/field-password/field-password.component';
import { Page } from '@tetra/pages/page/page.component';

@Component({
    selector: 'login-page',
    imports: [FieldTextComponent, FieldPasswordComponent],
    templateUrl: './login.page.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrls: ['./login.page.scss']
})

export class LoginPageComponent extends Page {
  public override title = 'Login';
  protected override requiresLogin = false;
  username: string = '';
  password: string = '';
  forgotPasswordMessage: string = '';
  loginError: string = '';

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
        self.loginError = 'Login failed. Please check your username and password.';
      }
    })
  }

  forgotPassword() {
    this.core.get('users', `password-reset?username=${this.username}&host=${encodeURI(window.location.origin) }`).then(response => {
      console.log('forgot password response', response);
      this.forgotPasswordMessage = response.message;
    })
  }

  override load() {
    if (this.currentUser && this.currentUser.id){
      this.goHome();
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
