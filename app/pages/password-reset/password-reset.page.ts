import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TetraPage } from '@tetra/page/page.component';

import { User } from '@tetra/user';
import { TetraFieldPasswordComponent } from '@tetra/field-password/field-password.component';

@Component({
		standalone: true,
    selector: "PasswordResetPage",
    imports: [CommonModule, TetraFieldPasswordComponent],
    templateUrl: './password-reset.page.html',
    styleUrl: './password-reset.page.scss'
})

export class TetraPasswordResetPage extends TetraPage {

  override title = 'Reset Password';
  override requiresLogin = false;
  override pageConfig: any = {
    showHeader: false,
    showTitle: false
  };
  token: string = '';
  newPassword: string = '';
  repeatNewPassword: string = '';
  resetSuccessMsg: string = '';

  override ngOnInit() {
    const self = this;
    let bodyClass = 'page-' + this.title;
    this.app.setBodyClass(bodyClass);
    return self.load();
  }

  override onLoad() {
    this.token = this.getParam('token', 'string');
      this.userService.loginByToken(this.token).then((data: any) => {
        this.app.setPageTitle("Reset password for " + this.currentUser.name());
      });
    // }
  }

  isValid(){
    return this.userService.validatePassword(this.newPassword);
  }
  resetPassword() {
    return this.userService.resetPassword(this.newPassword, this.repeatNewPassword).then((data: any) => {
      this.newPassword = '';
      this.repeatNewPassword = '';
      this.resetSuccessMsg = data.message;
    });
  }

  getRules() {
    return this.userService.passwordRules;
  }
}
