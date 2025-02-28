import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AppService } from '@tetra/app.service';

@Component({
		standalone: true,
    selector: 'nav.breadcrumbs',
    imports: [CommonModule],
    templateUrl: './breadcrumbs.component.html',
    styleUrl: './breadcrumbs.component.scss'
})
export class TetraBreadcrumbsComponent {
  breadcrumbs: Array<any> = [];
  constructor(
    protected app: AppService,
  ) {

    app.getBreadcrumbs().subscribe((breadcrumbs: Array<any>) => {
      this.breadcrumbs = breadcrumbs;
    });
  }
}
