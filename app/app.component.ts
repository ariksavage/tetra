import { Component, HostBinding, ElementRef, ViewChild } from '@angular/core';
import { UserService } from '@tetra/user.service';
import { User } from '@tetra/user';
import { CoreService } from '@tetra/core.service';
import { AppService } from '@tetra/app.service';
import { Title } from "@angular/platform-browser";

@Component({
		standalone: true,
    selector: 'tetra-app-root',
    templateUrl: './app.component.html',
    styleUrl: './app.component.scss'
})

export class TetraAppComponent {
  config: any = {
    name: 'Application'
  };
  pageTitle: string = '';
  pageConfig: any;
  year = new Date().getFullYear();
  user: User|null = null;
  copyright: string = '';
  @HostBinding('class') bodyClass = '';
  @ViewChild('appRoot') appRoot: ElementRef = {} as ElementRef;

  constructor(
    protected appService: AppService,
    protected userService: UserService,
    protected core: CoreService,
    protected titleService:Title
  ) {
    const self = this;
    // let element: ElementRef = this.app; //['components'][0].location;
    appService.getConfig().subscribe((config: any) => {
      self.config = config;
      self.titleService.setTitle(config.name);
    });
    appService.getPageTitle().subscribe((title: string) => {
      self.pageTitle = title;
      self.setTitle();
    });
    appService.getBodyClass().subscribe((bodyClass: string) => {
      self.setBodyClass(bodyClass);
    });
    appService.getPageConfig().subscribe((config: any) => {
      self.pageConfig = config;
    });
    userService.getUser().subscribe((user: User | null) => {
      if (user) {
        self.user = user;
      }
    });
    appService.init();
  }

  setTitle() {
    this.titleService.setTitle(this.config.name + ' | ' + this.pageTitle);
  }

  setBodyClass(bodyClass: string) {
    bodyClass = bodyClass.toLowerCase();
    bodyClass = bodyClass.replace(/[^a-z\-]+/g,"-");
  }
}
