import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { Route, Router, ActivatedRoute, NavigationEnd, NavigationStart } from '@angular/router';
import { CoreService } from './core.service';
import { Title } from "@angular/platform-browser";

@Injectable({
  providedIn: 'root'
})

export class AppService {
  isAdmin: boolean = false;
  private _pageTitle: string = '';
  private pageTitle = new BehaviorSubject<string>('');
  private _pageConfig: any = {
    showHeader: true,
    showTitle: true
  };
  private pageConfig = new BehaviorSubject<any>({});
  // private _pageIcon: string = '';
  // private pageIcon = new BehaviorSubject<string>('');
  // private _siteTitle: string = '';
  // private siteTitle = new BehaviorSubject<string>('');
  // private _navLinks: Array<any> = [];
  // private navLinks = new BehaviorSubject<Array<any>>([]);
  // private _subNavLinks: Array<any> = [];
  // private subNavLinks = new BehaviorSubject<Array<any>>([]);
  // private _breadcrumbs: Array<any> = [];
  // private breadcrumbs = new BehaviorSubject<Array<any>>([]);
  // private _config: any = {};
  // private config = new BehaviorSubject<any>({});
  replacements: Array<any> = [];

  constructor(private router: Router, protected activeRoute: ActivatedRoute, protected title:Title, protected core: CoreService) {
  }

  getConfig() {
    return this.core.get('core', 'app');
  }

  setPageTitle(title: string) {
    this._pageTitle = title;
    this.pageTitle.next(title);
  }

  getPageTitle() {
    return this.pageTitle.asObservable();
  }

  setPageConfig(config: any) {
    this._pageConfig = config;
    this.pageConfig.next(config);
  }

  getPageConfig() {
    return this.pageConfig.asObservable();
  }
}
