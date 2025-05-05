import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TetraFieldTextComponent } from '@tetra/field-text/field-text.component';
import { TetraFieldPasswordComponent } from '@tetra/field-password/field-password.component';
import { UserService } from '@tetra/user.service';

import { User } from '@tetra/user';
@Component({
		standalone: true,
    selector: '.login-form',
    imports: [CommonModule, TetraFieldTextComponent, TetraFieldPasswordComponent],
    templateUrl: './login-form.component.html',
    styleUrl: './login-form.component.scss'
})
export class TetraLoginForm {
  username: string = '';
  password: string = '';
  user: User|null = null;
  message: string = '';
  resetSent: boolean = false;
  @Output() cb = new EventEmitter<User>();


  constructor(
    protected userService: UserService
  ){}

  forgotPassword() {
    this.resetSent = true;
    this.userService.forgotPassword(this.username).then((data: any) => {
      this.message = data.message
    })
  }

  login() {
    const self = this;
    if (this.username && this.password){
      this.userService.login(this.username, this.password).then((user: User) => {
        self.cb.emit(user);
      });
    }
  }
}
