import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TetraPage } from '@tetra/page/page.component';

import { User } from '@tetra/user';

@Component({
	standalone: true,
  selector: "TetraError401Page",
  imports: [ CommonModule ],
  templateUrl: './401.page.html',
  styleUrl: './401.page.scss',
})

export class TetraError401Page extends TetraPage {

  override title = 'Error: 401';
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
      console.log('401', error);
      this.error = error;
      if (error && error.message){
        this.app.setPageTitle('401 | ' + error.message);
      } else {
        this.app.setPageTitle('Error 401');
      }
      this.app.setBodyClass('error error-401');
    });
  }
}
