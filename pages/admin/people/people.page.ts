import { Component, ChangeDetectionStrategy } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';

import { Page } from '@tetra/pages/page/page.component';
import { AdminRole, AdminUser, USER_STATUSES, statusLabel } from '@tetra/classes/admin';
import { AppLayoutComponent } from '@components/app-layout/app-layout.component';

/**
 * Every user account, filtered by name or email, role and status: the
 * shape of Drupal's People screen.
 */
@Component({
    selector: 'admin-people-page',
    imports: [AppLayoutComponent, RouterLink, FormsModule],
    templateUrl: './people.page.html',
    changeDetection: ChangeDetectionStrategy.Eager,
})
export class AdminPeoplePageComponent extends Page {
  public override title = 'People';
  readonly perPage = 50;
  readonly statuses = USER_STATUSES;
  readonly statusLabel = statusLabel;

  users: AdminUser[] = [];
  roles: AdminRole[] = [];
  userRoles: { [userId: string]: number[] } = {};
  pagination: any = null;
  loaded = false;

  search = '';
  role = 0;
  status = '';
  page = 1;

  override onLoad() {
    const query = this.activeRoute.snapshot.queryParamMap;
    this.search = query.get('search') ?? '';
    this.role = Number(query.get('role')) || 0;
    this.status = query.get('status') ?? '';
    this.page = Number(query.get('page')) || 1;
    this.core.get('roles', 'list').then((data: any) => this.roles = data?.roles ?? []);
    this.fetch();
  }

  fetch() {
    const params = new URLSearchParams({ per: String(this.perPage), page: String(this.page) });
    if (this.search.trim()) params.set('search', this.search.trim());
    if (this.role) params.set('role', String(this.role));
    if (this.status) params.set('status', this.status);
    this.core.get('users', 'list?' + params.toString()).then((data: any) => {
      this.users = data?.users ?? [];
      this.userRoles = data?.user_roles ?? {};
      this.pagination = data?.pagination ?? null;
      this.loaded = true;
    });
    // Keep the filters in the address, so a filtered list can be bookmarked or shared.
    this.route.navigate([], {
      relativeTo: this.activeRoute,
      replaceUrl: true,
      queryParams: {
        search: this.search.trim() || null,
        role: this.role || null,
        status: this.status || null,
        page: this.page > 1 ? this.page : null,
      },
    });
  }

  filter() {
    this.page = 1;
    this.fetch();
  }

  reset() {
    this.search = '';
    this.role = 0;
    this.status = '';
    this.filter();
  }

  filtered(): boolean {
    return !!(this.search || this.role || this.status);
  }

  goTo(page: number) {
    this.page = page;
    this.fetch();
  }

  name(user: AdminUser): string {
    return [user.first_name, user.last_name].filter(Boolean).join(' ');
  }

  roleLabels(user: AdminUser): string {
    const ids = this.userRoles[String(user.id)] ?? [];
    return this.roles.filter(r => ids.includes(r.id)).map(r => r.label).join(', ');
  }

  joined(user: AdminUser): string {
    return user.date_created ? new Date(user.date_created * 1000).toLocaleDateString() : '';
  }

  can(action: string): boolean {
    return !!this.currentUser?.hasPermission('users', action);
  }
}
