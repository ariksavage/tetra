import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TetraPage } from '@tetra/page/page.component';

import { User } from '@tetra/user';

@Component({
		standalone: true,
    selector: "PasswordResetPage",
    imports: [CommonModule],
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

  override ngOnInit(){
    const self = this;
    let bodyClass = 'page-' + this.title;
    this.app.setBodyClass(bodyClass);
    return self.load();
  }

  override onLoad() {
    this.token = this.getParam('token', 'string');
    if (!this.token){
      setTimeout(() => {
        console.log('delay');
        this.onLoad();
      }, 200);
    } else {
      this.userService.loginByToken(this.token).then((data: any) => {
        console.log('login by token', data);
      });
    }
  }
}
