import { Component, ChangeDetectionStrategy } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';

import { Page } from '@tetra/pages/page/page.component';
import { AdminRole, AdminUser, USER_STATUSES } from '@tetra/classes/admin';
import { AppLayoutComponent } from '@components/app-layout/app-layout.component';

/**
 * Add or edit one user account: details, status, roles and password, like
 * Drupal's user edit form. The API refuses changes that would lock you out
 * of your own account; the form says so up front.
 */
@Component({
    selector: 'admin-person-page',
    imports: [AppLayoutComponent, RouterLink, FormsModule],
    templateUrl: './person.page.html',
    changeDetection: ChangeDetectionStrategy.Eager,
})
export class AdminPersonPageComponent extends Page {
  public override title = 'Add user';
  readonly statuses = USER_STATUSES;

  userId = 0;
  user: AdminUser = { username: '', email: '', first_name: '', last_name: '', status: 'active' };
  roles: AdminRole[] = [];
  roleIds = new Set<number>();
  password = '';
  confirmPassword = '';
  saving = false;
  loaded = false;
  resetLink = '';
  resetText = '';

  override onLoad() {
    this.userId = Number(this.activeRoute.snapshot.paramMap.get('id')) || 0;
    const roles = this.core.get('roles', 'list').then((data: any) => this.roles = data?.roles ?? []);
    if (this.userId) {
      this.core.get('users', 'account', this.userId).then((data: any) => {
        if (data?.user) {
          this.setUser(data.user, data.role_ids ?? []);
        }
        this.loaded = true;
      });
    } else {
      // A new user starts with the default roles, as they would signing up.
      roles.then(() => {
        this.roleIds = new Set(this.roles.filter(r => r.default).map(r => r.id));
        this.loaded = true;
      });
    }
  }

  setUser(user: any, roleIds: number[]) {
    this.user = {
      id: user.id,
      username: user.username ?? '',
      email: user.email ?? '',
      first_name: user.first_name ?? '',
      last_name: user.last_name ?? '',
      status: user.status || 'active',
      date_created: user.date_created,
    };
    this.roleIds = new Set(roleIds.map(Number));
    this.title = [this.user.first_name, this.user.last_name].filter(Boolean).join(' ') || this.user.username;
    this.app.setPageTitle(this.title);
  }

  isNew(): boolean {
    return !this.userId;
  }

  isMe(): boolean {
    return !!this.userId && this.userId === this.currentUser?.id;
  }

  can(action: string, dimension: string = 'users'): boolean {
    return !!this.currentUser?.hasPermission(dimension, action);
  }

  editable(): boolean {
    return this.isNew() ? this.can('CREATE') : this.can('EDIT');
  }

  toggleRole(id: number, on: boolean) {
    on ? this.roleIds.add(id) : this.roleIds.delete(id);
  }

  /** The labels of the roles a role includes, eg. "Includes Admin, User". */
  includes(role: AdminRole): string {
    const names = this.roles.filter(r => role.inherits.includes(r.id)).map(r => r.label);
    return names.length ? 'Includes ' + names.join(', ') + '.' : '';
  }

  passwordProblem(): string {
    if (!this.password && !this.confirmPassword) {
      return this.isNew() ? 'A new user needs a password.' : '';
    }
    if (this.password.length < 8) {
      return 'Passwords need at least 8 characters.';
    }
    if (this.password !== this.confirmPassword) {
      return 'The passwords don\'t match.';
    }
    return '';
  }

  canSave(): boolean {
    const u = this.user;
    return this.editable() && !this.saving && !!(u.username.trim() && u.email.trim() && u.first_name.trim() && u.last_name.trim()) && !this.passwordProblem();
  }

  save() {
    if (!this.canSave()) {
      return;
    }
    this.saving = true;
    const payload: any = { user: { ...this.user, id: this.userId || undefined }, role_ids: [...this.roleIds] };
    if (this.password) {
      payload.password = this.password;
    }
    this.core.post('users', 'account', payload, false).then((data: any) => {
      this.saving = false;
      if (!data?.user) {
        return;
      }
      this.password = this.confirmPassword = '';
      if (this.isNew()) {
        this.route.navigate(['/admin/people', data.user.id], { replaceUrl: true });
        this.userId = data.user.id;
      }
      this.setUser(data.user, data.role_ids ?? []);
    }, () => this.saving = false);
  }

  /** Makes a one-time login link. It also logs the user out everywhere. */
  getResetLink() {
    if (!confirm('Get a password reset link? This logs them out of every device they\'re logged in on.')) {
      return;
    }
    this.core.get('users', 'password-reset-link', `${this.userId}?host=${encodeURIComponent(window.location.origin)}`).then((data: any) => {
      this.resetLink = data?.link ?? '';
      // The API's text carries <strong> for the day count; show it as plain text.
      this.resetText = String(data?.text ?? '').replace(/<[^>]+>/g, '');
    });
  }

  copyResetLink() {
    navigator.clipboard?.writeText(this.resetLink).then(() => this.message('Copied the link.', 'success'));
  }

  remove() {
    const name = this.title;
    if (!confirm(`Delete ${name}'s account? This can't be undone.`)) {
      return;
    }
    this.core.delete('users', 'id', this.userId).then((data: any) => {
      if (data) {
        this.route.navigate(['/admin/people']);
      }
    });
  }
}
