import { Component, ChangeDetectionStrategy, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import {  UserService  } from '@tetra/services/user.service';
import { User } from '@tetra/classes/user';
import { USER_MENU_ITEMS, UserMenuItem } from './user-menu-items';

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
  readonly items: UserMenuItem[] = inject(USER_MENU_ITEMS);

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

  canSee(item: UserMenuItem): boolean {
    if (!this.loggedIn() || !this.user) {
      return false;
    }
    return !item.permission || this.user.hasPermission(item.permission.dimension, item.permission.action);
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
