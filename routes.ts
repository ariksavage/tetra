import { Routes } from '@angular/router';

// import { InstallPage } from '@tetra/install/install.page';
import { LoginPage } from '@tetra/pages/login/login.page';
import { AdminDashboardPage } from '@tetra/pages/admin/dashboard/dashboard.page';
import { AdminConfigPage } from '@tetra/pages/admin/config/config.page';
import { AdminConfigMenuPage } from '@tetra/pages/admin/config/menu/menu.page';

const r : Routes = [
  { path: 'login', component:  LoginPage },
  { path: 'logout', redirectTo: '/', pathMatch: 'full' },
  { path: 'admin', title: 'Admin', children: [
      { path: '', redirectTo: '/admin/dashboard', pathMatch: 'prefix' },
      { path: 'dashboard', title: 'Dashboard', component: AdminDashboardPage },
      { path: 'config',  title: 'Config', children:
        [
          {path: '', component: AdminConfigPage },
          {path: 'menu', title: 'Menu', component: AdminConfigMenuPage }
        ]
      }
    ]
  }
];

export const tetraRoutes: Routes = r;
