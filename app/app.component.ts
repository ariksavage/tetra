/**
 * TetraAppComponent
 *
 * Tetra Application root component
 * 
 * This component will be extended by app specific functionality.
 *
 * @category   Component
 * @package    App
 * @author     Arik Savage <ariksavage@gmail.com>
 * @version    1.0
 * @since      2026-01-08
 */

import { Component, Directive, HostBinding, ElementRef, ViewChild, ChangeDetectorRef, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet, RouterLink } from '@angular/router';
import { Title } from "@angular/platform-browser";

import { UserService } from '@tetra/services/user.service';
import { User } from '@tetra/classes/user';
import { CoreService } from '@tetra/services/core.service';
import { AppService } from '@tetra/services/app.service';

@Component({
    selector: '[tetra-app-root]',
    imports: [RouterOutlet, RouterLink, CommonModule],
    templateUrl: './app.component.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './app.component.scss'
})

export class TetraAppComponent {
  /**
   * Application configuration
   * @var object;
   */
  public config: any = {
    name: 'Application',
    author: 'Author Name'
  };

  /**
   * Page Specific Configuration
   * @var object
   */
  pageConfig: any;

  /**
   * Page title
   * used to set the page's <title> tag
   * @var string
   */
  pageTitle = 'Default Page Title';

  /**
   * Current Year
   * 
   * Used in the app footer
   */
  year = new Date().getFullYear();

  /**
   * Currently logged in user
   */
  user: User|null = null;

  /**
   * Component constructor
   */
  constructor(
    protected appService: AppService,
    protected userService: UserService,
    protected core: CoreService,
    protected titleService:Title,
    private cdRef: ChangeDetectorRef
  ) {}

  /**
   * Function called by Angular on init
   */
  ngOnInit(){
    this.subscriptions();
    this.onInit();
  }

  /**
   * Stub to be overridden by appComponents
   */
  onInit() {
    // override
  }

  /**
   * Subscribe and handle data from the app service
   * 
   * - App Config
   * - Page Title
   * - Body Class
   * - Page Config
   * - User
   */
  subscriptions() {
    const self = this;
    this.appService.init();
    this.appService.getConfig().subscribe((config: any) => {
      if (config && Object.keys(config).length) {
        self.config = config;
        self.setTitle();
      }
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


  getCopyrightAfter() {
    return this.config.copyright_after;
  }
  getCopyrightBefore() {
    return this.config.copyright_before;
  }

  logout() {
    this.userService.logout();
  }

  setTitle() {
    this.titleService.setTitle(this.pageTitle + ' | ' + this.config.name);
    this.cdRef.detectChanges();
  }

  setBodyClass(bodyClass: string) {
    bodyClass = bodyClass.toLowerCase();
    bodyClass = bodyClass.replace(/[^a-z\-]+/g,"-");
  }
}
