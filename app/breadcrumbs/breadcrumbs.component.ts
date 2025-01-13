import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AppService } from '@tetra/app.service';

@Component({
  selector: 'nav.breadcrumbs',
  standalone: true,
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
      console.log('breadcrumbs', breadcrumbs);
      this.breadcrumbs = breadcrumbs;
    });
  }
}
