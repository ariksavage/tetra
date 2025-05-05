import { Directive, HostBinding, ElementRef, ViewChild, ChangeDetectorRef } from '@angular/core';
import { UserService } from '@tetra/user.service';
import { User } from '@tetra/user';
import { CoreService } from '@tetra/core.service';
import { AppService } from '@tetra/app.service';
import { Title } from "@angular/platform-browser";


@Directive()
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
    protected titleService:Title,
    private cdRef: ChangeDetectorRef
  ) {}

  ngOnInit() {
    const self = this;
    this.appService.getConfig().subscribe((config: any) => {
      self.config = config;
      self.titleService.setTitle(config.name);
    });
    this.appService.getPageTitle().subscribe((title: string) => {
      self.pageTitle = title;
      self.setTitle();
    });
    this.appService.getBodyClass().subscribe((bodyClass: string) => {
      self.setBodyClass(bodyClass);
    });
    this.appService.getPageConfig().subscribe((config: any) => {
      if (config){
        self.pageConfig = config;
      }
    });
    this.userService.getUser().subscribe((user: User | null) => {
      if (user) {
        self.user = user;
      }
    });
    this.appService.init();
  }

  setTitle() {
    this.titleService.setTitle(this.config.name + ' | ' + this.pageTitle);
    this.cdRef.detectChanges();
  }

  setBodyClass(bodyClass: string) {
    bodyClass = bodyClass.toLowerCase();
    bodyClass = bodyClass.replace(/[^a-z\-]+/g,"-");
  }
}
