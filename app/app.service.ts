import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { Route, Router, ActivatedRoute, NavigationEnd, NavigationStart } from '@angular/router';
import { CoreService } from './core.service';
import { UserService } from '@tetra/user.service';
import { Title } from "@angular/platform-browser";
import { MessageService } from '@tetra/message.service';

@Injectable({
  providedIn: 'root'
})

export class AppService {
  isAdmin: boolean = false;
  private _pageTitle: string = '';
  private pageTitle = new BehaviorSubject<string>('');

  private _bodyClass: string = '';
  private bodyClass = new BehaviorSubject<string>('');

  private _pageConfig: any = {
    showHeader: true,
    showTitle: true
  };
  private pageConfig = new BehaviorSubject<any>({});

  private _breadcrumbs: Array<any> = [];
  private breadcrumbs = new BehaviorSubject<any>([]);
  private _config: any = {};
  private config = new BehaviorSubject<any>({});
  private _error: any = {};
  private error = new BehaviorSubject<any>({});
  replacements: Array<any> = [];

  constructor(private router: Router, protected activeRoute: ActivatedRoute,
  protected title:Title,
  protected core: CoreService,
  protected userService: UserService,
  protected messages: MessageService) {
    router.events.subscribe((val: any) => {
      if (val instanceof NavigationEnd) {
        this.mapBreadcrumbs();
      }
    });
    messages.getMessage().subscribe((message: any) => {
      // console.log('app message', message);
    });
  }

  addBreadcrumbParent(title: string, path: string) {
    // console.log('add breadcrumb', this._breadcrumbs);
    this._breadcrumbs.push({
      title,
      path
    });
    this.breadcrumbs.next(this._breadcrumbs);
  }

  mapBreadcrumbs() {
    const routerConfig: any = {};
    for (let a=0;a<this.router.config.length; a++) {
      const route: any = this.router.config[a];
      if (typeof route.path == 'string' && route.path){
        const key: string = route.path.toString();
        if (typeof routerConfig[key] == 'undefined'){
          routerConfig[key] = route;
        } else {
          if (typeof routerConfig[key].children !== 'undefined'){
            routerConfig[key].children = routerConfig[key].children.concat(route.children);
          } else {
            routerConfig[key].children = route.children;
          }
        }
      }
    }
    let routes = Object.values(routerConfig);
    let lastPath = '';
    const crumbs: Array<any> = [];
    const urlSegments = window.location.pathname.split('/');
    urlSegments.pop();
    for (let i=0;i<urlSegments.length; i++) {
      let crumb = urlSegments[i];
      const crumbRoutes = routes.filter((item: any) => {
        return item.path == crumb
      });
      if (crumbRoutes.length){
        const crumbRoute: any = crumbRoutes[0];
        lastPath += '/' + crumbRoute.path;
        crumbs.push({
          title: crumbRoute.title,
          path: lastPath
        });
        routes = crumbRoute.children;
      }
    }
    this.setBreadcrumbs(crumbs);
  }

  getConfig() {
    return this.config.asObservable();
  }

  init() {
    return this.core.get('app', 'index').then((data: any) => {
      if (data && data.app && data.app.config){
        this.setConfig(data.app.config);
      }
      this.userService.loginByToken();
    });
  }

  setConfig(config: any) {
    this._config = config;
    this.config.next(this._config);
  }

  setPageTitle(title: string) {
    this._pageTitle = title;
    this.pageTitle.next(title);
  }

  getPageTitle() {
    return this.pageTitle.asObservable();
  }

  setBodyClass(bodyClass: string) {
    this._bodyClass = bodyClass;
    this.bodyClass.next(bodyClass);
  }

  getBodyClass() {
    return this.bodyClass.asObservable();
  }

  setPageConfig(config: any) {
    this._pageConfig = config;
    this.pageConfig.next(config);
  }

  getPageConfig() {
    return this.pageConfig.asObservable();
  }

  setError(error: any) {
    this._error = error;
    this.error.next(error);
  }

  getError() {
    return this.error.asObservable();
  }

  setBreadcrumbs(crumbs: Array<any>){
    this._breadcrumbs = crumbs;
    this.breadcrumbs.next(this._breadcrumbs);
  }

  getBreadcrumbs() {
    return this.breadcrumbs.asObservable();
  }
}
