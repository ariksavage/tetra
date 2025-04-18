import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { UserService } from '@tetra/user.service';
import { User } from '@tetra/user';
import { TetraButtonComponent } from '@tetra/button/button.component';

@Component({
		standalone: true,
    selector: '.user-menu',
    imports: [CommonModule, TetraButtonComponent],
    templateUrl: './user-menu.component.html',
    styleUrl: './user-menu.component.scss'
})
export class TetraUserMenuComponent {
  user?: User;
  open: boolean = false;

  constructor(private userService: UserService) {
    userService.getUser().subscribe((user: User | null) => {
      if (user) {
        this.user = user;
      }
    });
  }

  loggedIn() {
    return (this.user && this.user.id);
  }

  logout() {
    const self = this;
    this.userService.logout();
  }
  toggle() {
    this.open = !this.open;
  }
}
