import { Routes } from '@angular/router';

import { LoginPage } from '@tetra/pages/login/login.page';
import { AdminDashboardPage } from '@tetra/pages/admin/dashboard/dashboard.page';
import { AdminConfigPage } from '@tetra/pages/admin/config/config.page';
import { AdminConfigMenuPage } from '@tetra/pages/admin/config/menu/menu.page';
import { Error404Page } from '@tetra/pages/error/404/404.page';

const r : Routes = [
  { path: 'login', component:  LoginPage },
  { path: 'logout', redirectTo: '/', pathMatch: 'full' },
  { path: 'admin', title: 'Admin', children: [
    { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
    { path: 'dashboard', component: AdminDashboardPage },
    { path: 'config',  title: 'Config', children:
      [
        {path: '', component: AdminConfigPage },
        {path: 'menu', title: 'Menu', component: AdminConfigMenuPage }
      ]
    }
  ]},
  { path: '404', title: 'Error 404', component: Error404Page }
];

export const tetraRoutes: Routes = r;
