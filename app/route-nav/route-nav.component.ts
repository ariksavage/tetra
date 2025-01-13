import { Component } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { CoreService } from '@tetra/core.service';

@Component({
  selector: 'nav.route-nav',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './route-nav.component.html',
  styleUrl: './route-nav.component.scss'
})
export class RouteNavComponent {
  routeConfig : Array<any> = [];
  navItems: Array<any> =[];
  private matchedRoutes: Array<string> = [];
  root: string = 'admin';

  constructor(
    protected core: CoreService,
    protected route: Router,
    protected activeRoute: ActivatedRoute,
  ) {}

  ngOnInit() {
    this.getRoutes();
  }
  getRoutes() {
    const self = this;
    let rootPath = '';
    if (window.location.pathname.split('/')[1] == 'admin'){
      rootPath = 'admin';
    }
    return this.core.get('core', 'menu-tree', rootPath).then((data) => {
      console.log('menu', data.menu);
      self.navItems = data.menu.children;
    })
  }
}
