import { Component, ChangeDetectionStrategy } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';

import { Page } from '@tetra/pages/page/page.component';
import { AdminRole } from '@tetra/classes/admin';
import { AppLayoutComponent } from '@components/app-layout/app-layout.component';

/**
 * Every role, how many people have it and what it includes, with links to
 * edit it or its permissions: the shape of Drupal's Roles screen.
 */
@Component({
    selector: 'admin-roles-page',
    imports: [AppLayoutComponent, RouterLink, FormsModule],
    templateUrl: './roles.page.html',
    changeDetection: ChangeDetectionStrategy.Eager,
})
export class AdminRolesPageComponent extends Page {
  public override title = 'Roles';
  roles: AdminRole[] = [];
  loaded = false;
  newLabel = '';

  override onLoad() {
    this.fetch();
  }

  fetch() {
    this.core.get('roles', 'list').then((data: any) => {
      this.roles = data?.roles ?? [];
      this.loaded = true;
    });
  }

  includes(role: AdminRole): string {
    return this.roles.filter(r => role.inherits.includes(r.id)).map(r => r.label).join(', ');
  }

  can(action: string, dimension: string = 'roles'): boolean {
    return !!this.currentUser?.hasPermission(dimension, action);
  }

  add() {
    const label = this.newLabel.trim();
    if (!label) {
      return;
    }
    this.core.post('roles', 'save', { role: { label } }, false).then((data: any) => {
      if (data?.role) {
        this.route.navigate(['/admin/roles', data.role.id]);
      }
    });
  }
}
