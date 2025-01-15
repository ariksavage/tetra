import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { UserService } from '@tetra/user.service';
import { User } from '@tetra/user';
import { TetraButtonComponent } from '@tetra/button/button.component';

@Component({
  selector: '.user-menu',
  standalone: true,
  imports: [CommonModule, TetraButtonComponent],
  templateUrl: './user-menu.component.html',
  styleUrl: './user-menu.component.scss'
})
export class TetraUserMenuComponent {
  user: User = new User();
  open: boolean = false;

  constructor(
    private userService: UserService
  ){
   userService.getUser().subscribe((user: User) => this.user = user);
  }

  loggedIn() {
    if (this.user && this.user.id) {
      return true;
    } else {
      return false;
    }
  }

  logout() {
    const self = this;
    this.userService.logout();
  }
  toggle() {
    this.open = !this.open;
  }
}
