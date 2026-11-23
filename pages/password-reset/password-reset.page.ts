import { Component, ChangeDetectionStrategy } from '@angular/core';
import { RouterLink } from '@angular/router';

import { FieldPasswordComponent } from '@tetra/components/field-password/field-password.component';
import { Page } from '@tetra/pages/page/page.component';

/**
 * Set a new password with a reset link: /password-reset/{user ID}/{token}.
 * The token works once, and only for this; then you log in as usual.
 */
@Component({
    selector: 'password-reset-page',
    imports: [FieldPasswordComponent, RouterLink],
    templateUrl: './password-reset.page.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrls: ['../login/login.page.scss']
})
export class PasswordResetPage extends Page {
  public override title = 'Set a new password';
  protected override requiresLogin = false;
  userId: number = 0;
  token: string = '';
  password: string = '';
  password2: string = '';
  /** null while checking the link. */
  valid: boolean | null = null;
  formError = '';
  saving = false;

  override onLoad() {
    this.userId = this.getParam('id');
    this.token = this.getParam('token', 'string');
    this.userService.checkResetToken(this.userId, this.token).then(valid => this.valid = valid);
  }

  canSavePassword() {
    return this.password.length >= 8 && this.password == this.password2;
  }

  savePassword() {
    this.formError = '';
    if (this.password.length < 8) {
      this.formError = 'Passwords need at least 8 characters.';
      return;
    }
    if (this.password !== this.password2) {
      this.formError = "The passwords don't match.";
      return;
    }
    this.saving = true;
    return this.userService.resetPassword(this.userId, this.token, this.password, this.password2).then(username => {
      this.saving = false;
      if (username) {
        this.route.navigate(['/login'], { queryParams: { username, reset: 1 } });
      }
    });
  }
}
