import { Component, ChangeDetectionStrategy, computed, inject } from '@angular/core';

import { ConnectionService } from '@tetra/services/connection.service';
import { AppService } from '@tetra/services/app.service';

/**
 * A warning across the top of every page while the API or its database can't
 * be reached, saying what's wrong and (on a local copy) how to fix it. It
 * clears by itself once they're back. Put it once in the app's root template:
 *
 *   <div tetra-connection-banner></div>
 */
@Component({
    selector: '[tetra-connection-banner]',
    templateUrl: './connection-banner.component.html',
    changeDetection: ChangeDetectionStrategy.OnPush,
    styleUrl: './connection-banner.component.scss'
})
export class ConnectionBannerComponent {
  private connection = inject(ConnectionService);
  private app = inject(AppService);

  readonly problem = this.connection.problem;
  readonly local = this.app.isLocal();
  checking = false;

  readonly message = computed(() => {
    switch (this.problem()) {
      case 'unreachable':
        return this.local
          ? { title: "Can't reach the server.", detail: 'Is DDEV running? Start it with', command: 'ddev start' }
          : { title: "Can't reach the server.", detail: 'Check your connection. Nothing will save until it\'s back.', command: '' };
      case 'database':
        return this.local
          ? { title: "The database isn't available.", detail: "Is DDEV's database running? Try", command: 'ddev restart' }
          : { title: "The database isn't available right now.", detail: 'Nothing will save until it\'s back.', command: '' };
      default:
        return null;
    }
  });

  retry() {
    this.checking = true;
    this.connection.check().finally(() => this.checking = false);
  }
}
