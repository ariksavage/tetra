import { Component, ChangeDetectionStrategy } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { Observable, Subject, BehaviorSubject} from 'rxjs';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';

import { CoreService } from '@tetra/services/core.service';
import { AppService } from '@tetra/services/app.service';
import { UserService } from '@tetra/services/user.service';
import { ErrorService } from '@tetra/services/error.service';
import { User } from '@tetra/classes/user';


@Component({
    selector: 'app-page',
    imports: [],
    templateUrl: './page.component.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './page.component.scss'
})
export class Page {
  public title: string = 'Page Title';
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
    protected errorService: ErrorService,
    protected sanitizer: DomSanitizer,
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
    this.app.setPageTitle(this.title);
    this.userService.loginByToken().then((user: any)=> {
      self.currentUser = user;
      return self.load();
    }, (response: any) => {
      return self.load();
    });
  }

  error(message: string) {
    console.error(message);
    this.errorService.setError(message);
  }

  accessDenied(error: string = '') {
    if (error){
      this.error(error);
    }
    this.route.navigateByUrl('/error/401');
  }

  checkAccess() {
    return true;
  }

  private sanitized = new Map<string, SafeHtml>();

  /**
   * Mark HTML as safe to bind with [innerHTML].
   *
   * The result is remembered per string. Returning a new object on every call
   * would make Angular rebuild the bound element's contents on every change
   * detection pass, and replacing an element between mouse-down and mouse-up
   * means its click never arrives.
   */
  sanitize(html: string): SafeHtml {
    let safe = this.sanitized.get(html);
    if (!safe) {
      safe = this.sanitizer.bypassSecurityTrustHtml(html);
      this.sanitized.set(html, safe);
    }
    return safe;
  }

  message(text: string, type: string = '') {
    this.app.message(text, type);
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
