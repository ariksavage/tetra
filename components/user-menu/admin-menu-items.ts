import { InjectionToken } from '@angular/core';
import { User } from '@tetra/classes/user';
import { UserMenuItem } from './user-menu-items';

/**
 * Tetra's built-in admin pages. The user menu lists the ones the logged-in
 * user may open, under "Administration"; an admin sees all of them.
 */
export const TETRA_ADMIN_PAGES: UserMenuItem[] = [
  { label: 'People', route: '/admin/people', key: 'people', permission: { dimension: 'users', action: 'VIEW' } },
  { label: 'Roles', route: '/admin/roles', key: 'roles', permission: { dimension: 'roles', action: 'VIEW' } },
  { label: 'Permissions', route: '/admin/permissions', key: 'permissions', permission: { dimension: 'permissions', action: 'VIEW' } },
  { label: 'Configuration', route: '/admin/config', key: 'config', permission: { dimension: 'config', action: 'EDIT' } },
];

/**
 * The admin links in the user menu. Tetra's own pages by default; an app can
 * add its own admin pages or drop some:
 * `{ provide: ADMIN_MENU_ITEMS, useValue: [...TETRA_ADMIN_PAGES, { label: 'Billing', route: '/admin/billing', permission: {...} }] }`
 */
export const ADMIN_MENU_ITEMS = new InjectionToken<UserMenuItem[]>('ADMIN_MENU_ITEMS', {
  factory: () => TETRA_ADMIN_PAGES
});

/**
 * The menu items a user may see: logged in, and holding each item's
 * permission when it has one. Use it to put admin links in an app's own
 * navigation too.
 */
export function visibleMenuItems(items: UserMenuItem[], user: User | null | undefined): UserMenuItem[] {
  if (!user || !user.id) {
    return [];
  }
  return items.filter(item => !item.permission || user.hasPermission(item.permission.dimension, item.permission.action));
}
