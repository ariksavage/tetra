import { Component, ChangeDetectionStrategy } from '@angular/core';
import { RouterLink } from '@angular/router';

import { Page } from '@tetra/pages/page/page.component';

@Component({
    selector: 'home-page',
    imports: [RouterLink],
    templateUrl: './home.page.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './home.page.scss'
})
export class HomePageComponent extends Page {
  public override title = 'Home';
  protected override requiresLogin = false;
}
