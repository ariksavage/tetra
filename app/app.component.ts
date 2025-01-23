import { Component, HostBinding } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { UserService } from '@tetra/user.service';
import { User } from '@tetra/user';
import { CoreService } from '@tetra/core.service';
import { AppService } from '@tetra/app.service';
import { Title } from "@angular/platform-browser";

@Component({
  selector: 'tetra-app-root',
  standalone: true,
  imports: [RouterOutlet],
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

  constructor(
    protected appService: AppService,
    protected userService: UserService,
    protected core: CoreService,
    protected titleService:Title,
  ) {
    const self = this;
    appService.getConfig().subscribe((config: any) => {
      self.config = config;
      self.titleService.setTitle(config.name);
    });
    appService.getPageTitle().subscribe((title: string) => {
      self.pageTitle = title;
      self.setTitle();
      self.setBodyClass();
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

  setBodyClass() {
    this.bodyClass = 'page-' + this.pageTitle.toLowerCase().replace(' ', '-');
  }
}
