import { Component, ChangeDetectionStrategy } from '@angular/core';
import { RouterLink } from '@angular/router';

import { FieldTextComponent } from '@tetra/components/field-text/field-text.component';
import { Page } from '@tetra/pages/page/page.component';

/**
 * Forgot password: emails a link to set a new password to the account with
 * the username or email address given. The answer is the same either way.
 */
@Component({
    selector: 'forgot-password-page',
    imports: [FieldTextComponent, RouterLink],
    templateUrl: './forgot-password.page.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrls: ['../login/login.page.scss']
})
export class ForgotPasswordPageComponent extends Page {
  public override title = 'Forgot password';
  protected override requiresLogin = false;

  login = '';
  sent = '';
  formError = '';
  sending = false;

  override load() {
    this.login = this.activeRoute.snapshot.queryParamMap.get('login') ?? '';
  }

  send() {
    this.formError = '';
    if (!this.login.trim()) {
      this.formError = 'Enter your username or email address.';
      return;
    }
    this.sending = true;
    this.userService.forgotPassword(this.login.trim()).then((data: any) => {
      this.sending = false;
      if (data?.message) {
        this.sent = data.message;
      }
    });
  }
}
