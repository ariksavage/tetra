import { Component } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { Observable, Subject, BehaviorSubject} from 'rxjs';

import { CoreService } from '@tetra/core.service';
import { AppService } from '@tetra/app.service';
import { UserService } from '@tetra/user.service';
import { ErrorService } from '@tetra/error.service';
import { User } from '@tetra/user';


@Component({
		standalone: true,
    selector: 'app-page',
    imports: [],
    templateUrl: './page.component.html',
    styleUrl: './page.component.scss'
})
export class TetraPage {
  public title: string = 'Page';
  public currentUser: any = null;
  protected requiresLogin: boolean = true;
  protected pageConfig: any = {
    showHeader: true,
    showTitle: true
  };
  protected ready: boolean = false;
  protected routeParams: any = null;
  protected styles: string = '';

  constructor(
    public core: CoreService,
    protected app: AppService,
    protected route: Router,
    protected activeRoute: ActivatedRoute,
    protected userService: UserService,
    protected errorService: ErrorService
  ) {
    userService.getUser().subscribe((user: User | null) => {
      if (user) {
        this.currentUser = user;
      }
    });
  }

  ngOnInit() {
    const self = this;
    let bodyClass = 'page-' + this.title;
    this.app.setBodyClass(bodyClass);
    this.userService.loginByToken().then((user: any)=> {
      self.currentUser = user;
      return self.load();
    }, (response: any) => {
      return self.load();
    });
  }

  setStyles()
  {
    if (this.styles) {
      const head = document.getElementsByTagName('head')[0];
      const style = document.createElement('style');
      style.type = 'text/css';
      style.id = this.title;
      style.appendChild(document.createTextNode(this.styles));
      head.appendChild(style);
    }
  }

  checkPermissions() {

  }

  load() {
    this.app.setPageConfig(this.pageConfig);
    this.app.setPageTitle(this.title);
    if (this.requiresLogin && !(this.currentUser && this.currentUser.id)) {
      this.userService.loginRedirect();
    }
    this.activeRoute.paramMap.subscribe((params) => {
      this.routeParams = params;
    });
    this.onLoad();
    this.setStyles();
  }

  getParam(key: string, type: string = 'number'){
    let value = this.routeParams.get(key);

    if (type == 'number'){
      return parseInt(value);
    } else {
      return value;
    }
  }

  onLoad() {
    const self = this;
    this.ready = true;
  }
}
