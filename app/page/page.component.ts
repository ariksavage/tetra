import { Component } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { Title } from "@angular/platform-browser";
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

  constructor(
    protected core: CoreService,
    protected app: AppService,
    protected route: Router,
    protected activeRoute: ActivatedRoute,
    protected titleService:Title,
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

  load() {
    this.titleService.setTitle(this.title);
    if (this.requiresLogin && !this.user) {
      this.userService.loginRedirect();
    }
    return this.onLoad();
  }

  onLoad() {
    const self = this;
  }
}
