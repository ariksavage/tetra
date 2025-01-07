import { Routes } from '@angular/router';

// import { InstallPage } from '@tetra/install/install.page';
import { LoginPage } from '@tetra/pages/login/login.page';

const r : Routes = [
  { path: 'login', component:  LoginPage }
];

export const tetraRoutes: Routes = r;
