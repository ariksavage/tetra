import { Component } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { Observable, Subject, BehaviorSubject} from 'rxjs';

import { CoreService } from '@tetra/core.service';
import { AppService } from '@tetra/app.service';
import { UserService } from '@tetra/user.service';
import { User } from '@tetra/user';


@Component({
  selector: 'app-page',
  standalone: true,
  imports: [],
  templateUrl: './page.component.html',
  styleUrl: './page.component.scss'
})
export class TetraPage {
  public title: string = 'Page';
  protected user: User|null = null;
  protected requiresLogin: boolean = true;
  protected pageConfig: any = {
    showHeader: true,
    showTitle: true
  };
  protected ready: boolean = false;
  protected routeParams: any = null;

  constructor(
    public core: CoreService,
    protected app: AppService,
    protected route: Router,
    protected activeRoute: ActivatedRoute,
    protected userService: UserService
  ) {
    userService.getUser().subscribe((user: User | null) => {
      if (user) {
        this.user = user;
      }
    });
  }

  ngOnInit() {
    const self = this;
    this.userService.loginByToken().then((user: any)=> {
      self.user = user;
      return self.load();
    }, (response: any) => {
      return self.load();
    });
  }

  checkPermissions() {

  }

  load() {
    this.app.setPageConfig(this.pageConfig);
    this.app.setPageTitle(this.title);
    if (this.requiresLogin && !(this.user && this.user.id)) {
      this.userService.loginRedirect();
    }
    this.activeRoute.paramMap.subscribe((params) => {
      this.routeParams = params;
    });
    return this.onLoad();
  }

  getParam(key: string){
    let value = this.routeParams.get(key);

    if (!isNaN(value)){
      return value;
    } else {
      return parseInt(value);
    }
  }

  onLoad() {
    const self = this;
    this.ready = true;
  }
}
