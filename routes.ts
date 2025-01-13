import { Routes } from '@angular/router';

// import { InstallPage } from '@tetra/install/install.page';
import { LoginPage } from '@tetra/pages/login/login.page';
import { AdminDashboardPage } from '@tetra/pages/admin/dashboard/dashboard.page';
import { AdminConfigPage } from '@tetra/pages/admin/config/config.page';

const r : Routes = [
  { path: 'login', component:  LoginPage },
  { path: 'logout', redirectTo: '/', pathMatch: 'full' },
  { path: 'admin', children: [
      { path: '', component: AdminDashboardPage },
      { path: 'config',  component: AdminConfigPage }
    ]
  }
];

export const tetraRoutes: Routes = r;
