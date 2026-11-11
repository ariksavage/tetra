import { Component, ChangeDetectionStrategy, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import {  UserService  } from '@tetra/services/user.service';
import { User } from '@tetra/classes/user';
import { USER_MENU_ITEMS, UserMenuItem } from './user-menu-items';
import { ADMIN_MENU_ITEMS, visibleMenuItems } from './admin-menu-items';

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
  readonly adminPages: UserMenuItem[] = inject(ADMIN_MENU_ITEMS);

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
    return visibleMenuItems([item], this.user).length > 0;
  }

  /** The admin pages this user may open. */
  adminItems(): UserMenuItem[] {
    return visibleMenuItems(this.adminPages, this.user);
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
