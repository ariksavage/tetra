/**
 * Shapes used by Tetra's user, role and permission admin pages.
 */

export interface AdminRole {
  id: number;
  key: string;
  label: string;
  description: string;
  /** Given to every new user. */
  default: boolean;
  /** Tetra's root, admin and user roles: can't be deleted. */
  built_in: boolean;
  user_count: number;
  /** IDs of the roles this one includes. */
  inherits: number[];
}

export interface AdminPermission {
  id: number;
  dimension: string;
  action: string;
  title: string;
  description: string;
  /** The admin role always keeps this one. */
  locked: boolean;
}

export interface PermissionMatrix {
  permissions: AdminPermission[];
  roles: { id: number; key: string; label: string }[];
  /** Each role's own permission IDs, by role ID. */
  grants: { [roleId: string]: number[] };
  /** Permission IDs each role gets from the roles it includes. */
  inherited: { [roleId: string]: number[] };
}

export interface AdminUser {
  id?: number;
  username: string;
  email: string;
  first_name: string;
  last_name: string;
  status: string;
  date_created?: number;
}

export const USER_STATUSES = [
  { value: 'active', label: 'Active', description: 'Can log in.' },
  { value: 'pending', label: 'Pending', description: 'Not yet approved; can\'t log in.' },
  { value: 'deactivated', label: 'Deactivated', description: 'Blocked from logging in.' },
];

export function statusLabel(status: string): string {
  return USER_STATUSES.find(s => s.value === status)?.label ?? status;
}

/** "users" → "Users", "time_entries" → "Time entries" */
export function dimensionLabel(dimension: string): string {
  const words = dimension.replace(/[_-]+/g, ' ');
  return words.charAt(0).toUpperCase() + words.slice(1);
}
