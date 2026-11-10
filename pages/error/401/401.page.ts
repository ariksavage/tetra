import { Component, ChangeDetectionStrategy } from '@angular/core';

import { Page } from '@tetra/pages/page/page.component';

@Component({
    selector: 'error-401',
    imports: [],
    templateUrl: './401.page.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './401.page.scss'
})
export class AccessDeniedPageComponent extends Page {
  public override title = '401: Access Denied';
  protected override requiresLogin = false;
  public errorMessage: string = 'Test message';

  override onLoad() {
    this.errorService.getError().subscribe((message: string) => {
      if (message){
        this.errorMessage = message;
      }
    });
  }
}
