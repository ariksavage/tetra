import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TetraPage } from '@tetra/page/page.component';
import { FieldTextComponent } from '@tetra/field-text/field-text.component';
import { FieldPasswordComponent } from '@tetra/field-password/field-password.component';
import { User } from '@tetra/user';

@Component({
  selector: "LoginPage",
  standalone: true,
  imports: [ CommonModule, FieldTextComponent, FieldPasswordComponent ],
  templateUrl: './login.page.html',
  styleUrl: './login.page.scss',
})

export class LoginPage extends TetraPage {
  username: string = 'arik';
  password: string = 'Bier9wj7';
  override title = 'Login';
  override onLoad() {
    const self = this;
    if (this.user) {
      self.route.navigateByUrl('/');
    }
  }

  login() {
    const self = this;
    this.userService.login(this.username, this.password).then((user: User) => {
      self.route.navigateByUrl('/');
    });
  }
}
