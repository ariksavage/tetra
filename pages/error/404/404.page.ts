import { Component, ChangeDetectionStrategy } from '@angular/core';

import { Page } from '@tetra/pages/page/page.component';

@Component({
    selector: 'error-404',
    imports: [],
    templateUrl: './404.page.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './404.page.scss'
})
export class ErrorPageComponent extends Page {
  public override title = '404: Error';
  protected override requiresLogin = false;
  public errorMessage: string = '';

  override onLoad() {
    this.errorService.getError().subscribe((message: string) => {
      this.errorMessage = message;
    });
  }
}
