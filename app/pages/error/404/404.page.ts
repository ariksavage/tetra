import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TetraPage } from '@tetra/page/page.component';

import { User } from '@tetra/user';

@Component({
		standalone: true,
    selector: "TetraError404Page",
    imports: [CommonModule],
    templateUrl: './404.page.html',
    styleUrl: './404.page.scss'
})

export class TetraError404Page extends TetraPage {

  override title = 'Error: 404';
  override requiresLogin = false;
  error: any = null;

  override pageConfig: any = {
    showHeader: false,
    showTitle: false,
    titleInHeader: false,
    hideMenu: true,
  };

  override onLoad() {
    const self = this;
    this.errorService.getError().subscribe((error: any) => {
      this.error = error;
      if (error){
        this.app.setPageTitle('404 | ' + error.message);
      } else {
        this.app.setPageTitle('Error 404');
      }
      this.app.setBodyClass('error error-404');
    });
  }
}
