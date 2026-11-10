import { InjectionToken } from '@angular/core';

/**
 * A link an app adds to the user menu, between My Account and Configuration.
 * Shown only to a logged-in user, and only to one holding `permission` when
 * it is set.
 */
export interface UserMenuItem {
  label: string;
  route: string;
  /** Extra class on the menu item, for styling. */
  key?: string;
  permission?: { dimension: string; action: string };
}

/**
 * The app's own user menu links. Provide them in the app config:
 * `{ provide: USER_MENU_ITEMS, useValue: [{ label: 'Meal Plan', route: '/plan' }] }`
 */
export const USER_MENU_ITEMS = new InjectionToken<UserMenuItem[]>('USER_MENU_ITEMS', {
  factory: () => []
});
