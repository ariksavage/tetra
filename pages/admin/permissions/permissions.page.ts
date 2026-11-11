import { Component, ChangeDetectionStrategy } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';

import { Page } from '@tetra/pages/page/page.component';
import { AdminPermission, PermissionMatrix, dimensionLabel } from '@tetra/classes/admin';
import { AppLayoutComponent } from '@components/app-layout/app-layout.component';

interface PermissionGroup {
  dimension: string;
  label: string;
  permissions: AdminPermission[];
}

type Role = PermissionMatrix['roles'][number];

/**
 * Which role can do what: one row per permission, grouped by what it's
 * about, one column per role, like Drupal's permissions screen.
 * /admin/permissions/:roleId shows a single role's column.
 *
 * A box that's ticked and greyed out is a permission the role already has
 * another way: from a role it includes, or from an "All ..." permission.
 */
@Component({
    selector: 'admin-permissions-page',
    imports: [AppLayoutComponent, RouterLink, FormsModule],
    templateUrl: './permissions.page.html',
    styleUrl: './permissions.page.scss',
    changeDetection: ChangeDetectionStrategy.Eager,
})
export class AdminPermissionsPageComponent extends Page {
  public override title = 'Permissions';

  matrix: PermissionMatrix | null = null;
  groups: PermissionGroup[] = [];
  roles: Role[] = [];
  /** Each role's own permissions, as edited. */
  own = new Map<number, Set<number>>();
  saved = new Map<number, string>();
  onlyRole = 0;
  saving = false;

  override onLoad() {
    this.onlyRole = Number(this.activeRoute.snapshot.paramMap.get('roleId')) || 0;
    this.fetch();
  }

  fetch() {
    this.core.get('permissions', 'matrix').then((data: any) => {
      const matrix: PermissionMatrix | undefined = data?.matrix;
      if (!matrix) {
        return;
      }
      this.matrix = matrix;
      this.roles = this.onlyRole ? matrix.roles.filter(r => r.id === this.onlyRole) : matrix.roles;
      if (this.onlyRole && this.roles.length) {
        this.title = `${this.roles[0].label} permissions`;
        this.app.setPageTitle(this.title);
      }
      const groups = new Map<string, PermissionGroup>();
      for (const p of matrix.permissions) {
        if (!groups.has(p.dimension)) {
          groups.set(p.dimension, { dimension: p.dimension, label: dimensionLabel(p.dimension), permissions: [] });
        }
        groups.get(p.dimension)!.permissions.push(p);
      }
      // "All ..." first in each group: it covers the rest.
      for (const g of groups.values()) {
        g.permissions.sort((a, b) => (a.action === '*' ? -1 : 0) - (b.action === '*' ? -1 : 0));
      }
      this.groups = [...groups.values()];
      this.own.clear();
      this.saved.clear();
      for (const role of matrix.roles) {
        const ids = matrix.grants[String(role.id)] ?? [];
        this.own.set(role.id, new Set(ids));
        this.saved.set(role.id, this.key(ids));
      }
    });
  }

  private key(ids: Iterable<number>): string {
    return [...ids].sort((a, b) => a - b).join(',');
  }

  canEdit(): boolean {
    return !!this.currentUser?.hasPermission('permissions', 'EDIT');
  }

  has(role: Role, p: AdminPermission): boolean {
    return this.own.get(role.id)?.has(p.id) ?? false;
  }

  /** Every permission the role has, its own and inherited. */
  private effective(role: Role): Set<number> {
    const ids = new Set(this.own.get(role.id) ?? []);
    for (const id of this.matrix?.inherited[String(role.id)] ?? []) {
      ids.add(id);
    }
    return ids;
  }

  /**
   * Why the role already has this permission without ticking it, or ''.
   */
  includedBy(role: Role, p: AdminPermission): string {
    if (role.key === 'admin' && p.locked) {
      return 'Admins always keep this, so no one can lock everyone out.';
    }
    if (this.has(role, p)) {
      return '';
    }
    if ((this.matrix?.inherited[String(role.id)] ?? []).includes(p.id)) {
      return 'Included through a role this one includes.';
    }
    const effective = this.effective(role);
    const group = this.groups.find(g => g.dimension === p.dimension);
    const all = group?.permissions.find(x => x.action === '*');
    if (all && all.id !== p.id && effective.has(all.id)) {
      return `Included in ${all.title}.`;
    }
    if (p.action.endsWith(' OWN')) {
      const broader = group?.permissions.find(x => x.action === p.action.replace(' OWN', ''));
      if (broader && effective.has(broader.id)) {
        return `Included in ${broader.title}.`;
      }
    }
    return '';
  }

  toggle(role: Role, p: AdminPermission, on: boolean) {
    const set = this.own.get(role.id);
    if (set) {
      on ? set.add(p.id) : set.delete(p.id);
    }
  }

  changedRoles(): Role[] {
    return (this.matrix?.roles ?? []).filter(r => this.key(this.own.get(r.id) ?? []) !== this.saved.get(r.id));
  }

  save() {
    const changed = this.changedRoles();
    if (!changed.length || this.saving) {
      return;
    }
    const grants: { [roleId: string]: number[] } = {};
    for (const role of changed) {
      grants[String(role.id)] = [...(this.own.get(role.id) ?? [])];
    }
    this.saving = true;
    this.core.post('permissions', 'matrix', { grants }, false).then(() => {
      this.saving = false;
      this.fetch();
      // Your own permissions may have changed; refresh what the menus show.
      this.userService.loginByToken();
    }, () => this.saving = false);
  }

  discard() {
    this.fetch();
  }
}
