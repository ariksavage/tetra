import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TetraFieldTextComponent } from '@tetra/field-text/field-text.component';
import { TetraFieldPasswordComponent } from '@tetra/field-password/field-password.component';
import { UserService } from '@tetra/user.service';

import { User } from '@tetra/user';
@Component({
  selector: '.login-form',
  standalone: true,
  imports: [ CommonModule, TetraFieldTextComponent, TetraFieldPasswordComponent ],
  templateUrl: './login-form.component.html',
  styleUrl: './login-form.component.scss'
})
export class TetraLoginForm {
  username: string = '';
  password: string = '';
  user: User|null = null;
  @Output() cb = new EventEmitter<User>();


  constructor(
    protected userService: UserService
  ){}

  test(){
    alert('submit');
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
