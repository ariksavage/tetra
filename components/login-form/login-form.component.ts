import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FieldTextComponent } from '@tetra/components/field-text/field-text.component';
import { FieldPasswordComponent } from '@tetra/components/field-password/field-password.component';
import {  UserService  } from '@tetra/services/user.service';

import { User } from '@tetra/classes/user';
@Component({
		standalone: true,
    selector: '.login-form',
    imports: [CommonModule, FieldTextComponent, FieldPasswordComponent],
    templateUrl: './login-form.component.html',
    styleUrl: './login-form.component.scss'
})
export class LoginForm {
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
