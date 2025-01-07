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
  private _pageIcon: string = '';
  private pageIcon = new BehaviorSubject<string>('');
  private _siteTitle: string = '';
  private siteTitle = new BehaviorSubject<string>('');
  private _navLinks: Array<any> = [];
  private navLinks = new BehaviorSubject<Array<any>>([]);
  private _subNavLinks: Array<any> = [];
  private subNavLinks = new BehaviorSubject<Array<any>>([]);
  private _breadcrumbs: Array<any> = [];
  private breadcrumbs = new BehaviorSubject<Array<any>>([]);
  private _config: any = {};
  private config = new BehaviorSubject<any>({});
  replacements: Array<any> = [];

  constructor(private router: Router, protected activeRoute: ActivatedRoute, protected title:Title, protected core: CoreService) {

  }
}
