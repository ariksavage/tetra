import { Component, ChangeDetectionStrategy } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';

import { Page } from '@tetra/pages/page/page.component';
import { AdminRole } from '@tetra/classes/admin';
import { AppLayoutComponent } from '@components/app-layout/app-layout.component';

/**
 * Edit one role: its name, description, whether new users get it, and
 * which roles it includes.
 */
@Component({
    selector: 'admin-role-page',
    imports: [AppLayoutComponent, RouterLink, FormsModule],
    templateUrl: './role.page.html',
    changeDetection: ChangeDetectionStrategy.Eager,
})
export class AdminRolePageComponent extends Page {
  public override title = 'Role';
  roleId = 0;
  role: AdminRole | null = null;
  roles: AdminRole[] = [];
  label = '';
  description = '';
  isDefault = false;
  inherits = new Set<number>();
  saving = false;

  override onLoad() {
    this.roleId = Number(this.activeRoute.snapshot.paramMap.get('id')) || 0;
    this.fetch();
  }

  fetch() {
    this.core.get('roles', 'list').then((data: any) => {
      this.roles = data?.roles ?? [];
      this.role = this.roles.find(r => r.id === this.roleId) ?? null;
      if (!this.role) {
        this.error('That role doesn\'t exist.');
        this.route.navigate(['/admin/roles']);
        return;
      }
      this.label = this.role.label;
      this.description = this.role.description;
      this.isDefault = this.role.default;
      this.inherits = new Set(this.role.inherits);
      this.title = this.role.label;
      this.app.setPageTitle(this.title);
    });
  }

  others(): AdminRole[] {
    return this.roles.filter(r => r.id !== this.roleId);
  }

  toggleInherit(id: number, on: boolean) {
    on ? this.inherits.add(id) : this.inherits.delete(id);
  }

  can(action: string, dimension: string = 'roles'): boolean {
    return !!this.currentUser?.hasPermission(dimension, action);
  }

  save() {
    if (!this.label.trim() || this.saving) {
      return;
    }
    this.saving = true;
    this.core.post('roles', 'save', {
      role: { id: this.roleId, label: this.label, description: this.description, default: this.isDefault, inherits: [...this.inherits] }
    }, false).then(() => {
      this.saving = false;
      this.fetch();
    }, () => this.saving = false);
  }

  remove() {
    if (!this.role || !confirm(`Delete the ${this.role.label} role? The ${this.role.user_count} people who have it will lose it. This can't be undone.`)) {
      return;
    }
    this.core.delete('roles', 'id', this.roleId).then((data: any) => {
      if (data) {
        this.route.navigate(['/admin/roles']);
      }
    });
  }
}
