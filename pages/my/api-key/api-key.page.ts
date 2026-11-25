import { Component, ChangeDetectionStrategy } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';

import { Page } from '@tetra/pages/page/page.component';
import { AppLayoutComponent } from '@components/app-layout/app-layout.component';
import { dimensionLabel } from '@tetra/classes/admin';
import { ApiKeyAccess, ApiKeyDetail, ApiKeyLogEntry, API_KEY_EXPIRY, formatKeyDate } from '@tetra/classes/api-keys';

/** A dimension's permissions, as the page lists them. */
interface PermissionGroup {
  dimension: string;
  label: string;
  permissions: ApiKeyDetail['permissions'];
}

/**
 * One API key: its token (once, just after it's made), its name and expiry,
 * what it may do (the roles its own role inherits, and permissions given to
 * it directly), what that comes to, what it has changed, and revoking it.
 */
@Component({
  selector: 'my-api-key-page',
  imports: [AppLayoutComponent, RouterLink, FormsModule],
  templateUrl: './api-key.page.html',
  styleUrl: './api-key.page.scss',
  changeDetection: ChangeDetectionStrategy.Eager,
})
export class MyApiKeyPageComponent extends Page {
  public override title = 'API key';
  keyId = 0;
  key: ApiKeyDetail | null = null;
  /** Shown once, just after the key is made; never fetched again. */
  token = '';
  copied = false;
  name = '';
  /** -1: leave the expiry as it is. */
  expiresDays = -1;
  inherits = new Set<number>();
  permissions = new Set<number>();
  groups: PermissionGroup[] = [];
  log: ApiKeyLogEntry[] = [];
  saving = false;
  readonly expiry = API_KEY_EXPIRY;
  formatKeyDate = formatKeyDate;
  dimensionLabel = dimensionLabel;

  override onLoad() {
    this.keyId = Number(this.activeRoute.snapshot.paramMap.get('id')) || 0;
    // Passed by the list page when it made the key. Cleared from history so
    // going back or reloading doesn't show it again.
    const state = history.state ?? {};
    if (typeof state.token === 'string' && state.token) {
      this.token = state.token;
      history.replaceState({ ...state, token: undefined }, '');
    }
    this.fetch();
  }

  fetch() {
    Promise.all([
      this.core.get('api-keys', 'id', this.keyId),
      this.core.get('api-keys', 'log', this.keyId),
    ]).then(([data, log]: any[]) => {
      const key: ApiKeyDetail | null = data?.key ?? null;
      if (!key) {
        this.route.navigate(['/my/api-keys']);
        return;
      }
      this.setKey(key);
      this.log = log?.log ?? [];
    });
  }

  private setKey(key: ApiKeyDetail) {
    this.key = key;
    this.name = key.name;
    this.expiresDays = -1;
    this.inherits = new Set(key.inherits);
    this.permissions = new Set(key.permission_ids);
    const groups = new Map<string, PermissionGroup>();
    for (const p of key.permissions) {
      const group = groups.get(p.dimension) ?? { dimension: p.dimension, label: dimensionLabel(p.dimension), permissions: [] };
      group.permissions.push(p);
      groups.set(p.dimension, group);
    }
    this.groups = [...groups.values()];
    this.title = key.name;
    this.app.setPageTitle(key.name);
  }

  can(action: string): boolean {
    return !!this.currentUser?.hasPermission('api_keys', action);
  }

  isActive(): boolean {
    return this.key?.status === 'active';
  }

  isInherited(id: number): boolean {
    return !!this.key?.inherited_permission_ids.includes(id);
  }

  toggle(set: Set<number>, id: number, on: boolean) {
    on ? set.add(id) : set.delete(id);
  }

  /** "clients: VIEW, EDIT" lines for what a key can (or can't) do. */
  accessLines(access: ApiKeyAccess | undefined): { label: string; actions: string }[] {
    return Object.entries(access ?? {}).map(([dimension, actions]) => ({ label: dimensionLabel(dimension), actions: actions.join(', ') }));
  }

  copyToken() {
    navigator.clipboard?.writeText(this.token).then(() => this.copied = true);
  }

  save() {
    if (!this.key || this.saving || !this.name.trim()) {
      return;
    }
    this.saving = true;
    const body: any = { id: this.keyId, name: this.name, inherits: [...this.inherits], permissions: [...this.permissions] };
    if (this.expiresDays >= 0) {
      body.expires_days = Number(this.expiresDays);
    }
    this.core.post('api-keys', 'save', body, false).then((data: any) => {
      this.saving = false;
      if (data?.key) {
        this.setKey(data.key);
      }
    }, () => this.saving = false);
  }

  revoke() {
    if (!this.key || !confirm(`Revoke "${this.key.name}"? It stops working at once, and can't be turned back on.`)) {
      return;
    }
    this.core.post('api-keys', 'revoke', { id: this.keyId }, false).then((data: any) => {
      if (data?.key) {
        this.token = '';
        this.setKey(data.key);
      }
    });
  }

  remove() {
    if (!this.key || !confirm(`Delete "${this.key.name}" and its activity log? This can't be undone.`)) {
      return;
    }
    this.core.delete('api-keys', 'id', this.keyId).then((data: any) => {
      if (data) {
        this.route.navigate(['/my/api-keys']);
      }
    });
  }
}
