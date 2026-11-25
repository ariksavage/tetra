import { Component, ChangeDetectionStrategy } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';

import { Page } from '@tetra/pages/page/page.component';
import { AppLayoutComponent } from '@components/app-layout/app-layout.component';
import { ApiKey, API_KEY_EXPIRY, formatKeyDate } from '@tetra/classes/api-keys';

/**
 * Your API keys: what you've made for programs (eg. Claude) to use the API
 * as you, and a form to make another. A new key opens on its own page, where
 * its token is shown once and what it may do is set.
 */
@Component({
  selector: 'my-api-keys-page',
  imports: [AppLayoutComponent, RouterLink, FormsModule],
  templateUrl: './api-keys.page.html',
  changeDetection: ChangeDetectionStrategy.Eager,
})
export class MyApiKeysPageComponent extends Page {
  public override title = 'API keys';
  keys: ApiKey[] = [];
  loaded = false;
  /** With api_keys *: everyone's keys, not just yours. */
  showAll = false;
  newKey = { name: '', expires_days: 0 };
  creating = false;
  readonly expiry = API_KEY_EXPIRY;
  formatKeyDate = formatKeyDate;

  override onLoad() {
    this.fetch();
  }

  canManageAll(): boolean {
    return !!this.currentUser?.hasPermission('api_keys', '*');
  }

  canCreate(): boolean {
    return !!this.currentUser?.hasPermission('api_keys', 'CREATE');
  }

  fetch() {
    this.core.get('api-keys', this.showAll ? 'list?all=1' : 'list').then((data: any) => {
      this.keys = data?.keys ?? [];
      this.loaded = true;
    });
  }

  create() {
    if (!this.newKey.name.trim() || this.creating) {
      return;
    }
    this.creating = true;
    this.core.post('api-keys', 'create', { name: this.newKey.name, expires_days: Number(this.newKey.expires_days) }, false).then((data: any) => {
      this.creating = false;
      if (data?.key) {
        // The token goes to the key's page in navigation state only: shown
        // once there, and gone on reload.
        this.route.navigate(['/my/api-keys', data.key.id], { state: { token: data.token } });
      }
    }, () => this.creating = false);
  }
}
