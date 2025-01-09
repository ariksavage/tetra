import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { UserService } from '@tetra/user.service';
import { User } from '@tetra/user';
@Component({
  selector: 'nav.user-menu',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './user-menu.component.html',
  styleUrl: './user-menu.component.scss'
})
export class TetraUserMenuComponent {
  user: User|null = null;

  constructor(
    private userService: UserService
  ){
   userService.getUser().subscribe((user: User|null) => this.user = user);
  }

  logout() {
    this.user = null;
    this.userService.logout();
  }
}
