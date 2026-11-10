import { Component, ChangeDetectionStrategy } from '@angular/core';

import { Page } from '@tetra/pages/page/page.component';
import { FieldTextComponent } from '@tetra/components/field-text/field-text.component';
import { FieldPasswordComponent } from '@tetra/components/field-password/field-password.component';
import { FieldEmailComponent } from '@tetra/components/field-email/field-email.component';
import { User } from '@tetra/classes/user';
import { AppLayoutComponent } from '@components/app-layout/app-layout.component';

@Component({
    selector: 'my-account-page',
    imports: [FieldTextComponent, FieldEmailComponent, FieldPasswordComponent, AppLayoutComponent],
    templateUrl: './account.page.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './account.page.scss'
})
export class MyAccountPageComponent extends Page {
  public override title = 'My Account';
  account: User = new User();
  password: string = '';
  password2: string = '';
  newEmail: string = '';
  emailConfirmationCode: string = '';
  emailConfirmationSent: boolean = false;

  override onLoad() {
    this.account = new User({...this.currentUser});
  }

  confirmEmail() {
    const payload = {
      "user": {
        "id": this.account.id,
        "email_address": this.newEmail
      }
    }
    this.core.post('users', 'email', payload).then(response => {
      this.emailConfirmationSent = true;
      console.log('confirm email response', response);
    });
  }
  updateEmail() {
    const payload = {
      "user": {
        "id": this.account.id,
        "email_address": this.newEmail,
        "security": this.emailConfirmationCode
      }
    }
    this.core.post('users', 'email', payload).then(response => {
      this.account.email = this.newEmail;
      this.emailConfirmationSent = false;
      this.emailConfirmationCode = '';
      this.newEmail = '';
    });
  }

  saveUser() {
    const payload: any = {};
    payload.user = this.account;
    delete payload.user.password;
    this.core.post('users', 'save', payload).then((response) => {
      this.userService.setUser(response.user);
    })
  }

  resetPassword() {
    const payload: any = {
      user: {
        id: this.account.id,
        password: this.password
      }
    };
    this.core.post('users', 'save', payload).then((response) => {
      console.log('update password', response);
      this.userService.setUser(response.user);
    })
  }
}
