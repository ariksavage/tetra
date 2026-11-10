import { Component, ChangeDetectionStrategy } from '@angular/core';
import { RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import {  UserService  } from '@tetra/services/user.service';
import { User } from '@tetra/classes/user';

@Component({
    selector: '.user-menu',
    imports: [CommonModule, RouterLink],
    templateUrl: './user-menu.component.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './user-menu.component.scss'
})
export class UserMenuComponent {
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

  logout(event?: Event) {
    // The link has a real href for accessibility, but logging out happens in the app.
    event?.preventDefault();
    this.open = false;
    this.userService.logout();
  }
  toggle() {
    this.open = !this.open;
  }
}
