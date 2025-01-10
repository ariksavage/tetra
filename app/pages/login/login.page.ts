import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TetraPage } from '@tetra/page/page.component';
import { TetraLoginForm } from '@tetra/login-form/login-form.component';

import { User } from '@tetra/user';

@Component({
  selector: "LoginPage",
  standalone: true,
  imports: [ CommonModule, TetraLoginForm],
  templateUrl: './login.page.html',
  styleUrl: './login.page.scss',
})

export class LoginPage extends TetraPage {

  override title = 'Login';
  override requiresLogin = false;

  override onLoad() {
    const self = this;
    if (this.user && this.user.id) {
      this.redirect();
    }
  }

  redirect() {
    this.route.navigateByUrl('/');
  }
}
