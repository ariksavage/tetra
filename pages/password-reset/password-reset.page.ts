import { Component, ChangeDetectionStrategy } from '@angular/core';

import { FieldPasswordComponent } from '@tetra/components/field-password/field-password.component';
import { Page } from '@tetra/pages/page/page.component';

@Component({
    selector: 'password-reset-page',
    imports: [FieldPasswordComponent],
    templateUrl: './password-reset.page.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrls: ['./password-reset.page.scss']
})

export class PasswordResetPage extends Page {
  public override title = 'Password Reset';
  protected override requiresLogin = false;
  userId: number = 0;
  token: string = '';
  password: string = '';
  password2: string = '';

  override onLoad() {
    this.userId = this.getParam('id');
    this.token = this.getParam('token', 'string');
    this.userService.loginByToken(this.token).then((user) => {
      console.log('logged in as ', user);
      this.userService.deleteAuthCookie();
    });
  }

  canSavePassword() {
    return this.password && this.password == this.password2;
  }

  savePassword() {
    const payload = {
      user: {
        id: this.userId,
        password: this.password
      }
    };
    console.log(payload);
    return this.core.post('users', 'save', payload).then(response => {

    });
  }
}
