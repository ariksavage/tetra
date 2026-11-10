import { Routes } from '@angular/router';

import { permissionGuard } from '@tetra/guards/permission.guard';

// Each page is loaded when first visited, keeping it out of the app's initial bundle.
export const TETRA_ROUTES: Routes = [
  { path: '', redirectTo: 'home', pathMatch: 'full' },
  { path: 'home', loadComponent: () => import('@tetra/pages/home/home.page').then(m => m.HomePageComponent), title: 'Home' },
  { path: 'install', loadComponent: () => import('@tetra/pages/install/install.page').then(m => m.InstallPageComponent), title: 'Install' },
  { path: 'login', loadComponent: () => import('@tetra/pages/login/login.page').then(m => m.LoginPageComponent), title: 'Login' },
  { path: 'password-reset/:id/:token', loadComponent: () => import('@tetra/pages/password-reset/password-reset.page').then(m => m.PasswordResetPage), title: 'Password Reset'},
  { path: 'my', children: [
    { path: '', redirectTo: 'account', pathMatch: 'full' },
    { path: 'account', loadComponent: () => import('@tetra/pages/my/account/account.page').then(m => m.MyAccountPageComponent), title: 'My Account' }
  ]},
  { path: 'admin', children: [
    { path: '', redirectTo: 'config', pathMatch: 'full' },
    { path: 'config', loadComponent: () => import('@tetra/pages/admin/configuration/config.page').then(m => m.ConfigPageComponent), title: 'Configuration', canActivate: [permissionGuard('config', 'EDIT')] }
  ]},
  { path: 'error',children: [
    {path: '401', loadComponent: () => import('@tetra/pages/error/401/401.page').then(m => m.AccessDeniedPageComponent), title: 'Access Denied'},
    {path: '404', loadComponent: () => import('@tetra/pages/error/404/404.page').then(m => m.ErrorPageComponent), title: 'Error'}
  ]}
];
