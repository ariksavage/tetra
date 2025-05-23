import { Component } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { CoreService } from '@tetra/core.service';

@Component({
		standalone: true,
    selector: 'nav.app-menu',
    imports: [CommonModule],
    templateUrl: './app-menu.component.html',
    styleUrl: './app-menu.component.scss'
})
export class TetraAppMenuComponent {
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
    return this.core.get('app', 'menu-tree', rootPath).then((data) => {
      if (data && data.menu && data.menu.children){
        self.navItems = data.menu.children;
      }
    })
  }

  isCurrent(route: any) {
    return route.path == window.location.pathname;
  }
  inPath(route: any) {
    return route.path !== '/' && !this.isCurrent(route) && window.location.pathname.indexOf(route.path) > -1;
  }
}
