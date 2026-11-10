import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { UserService } from '@tetra/services/user.service';
import { User } from '@tetra/classes/user';

/**
 * Route guard for the site root: sends visitors to a landing page
 * depending on whether they are logged in.
 *
 * Usage: { path: '', pathMatch: 'full', canActivate: [landingGuard('/dashboard', '/recipes')], children: [] }
 */
export function landingGuard(loggedInUrl: string, anonymousUrl: string): CanActivateFn {
  return async () => {
    const router = inject(Router);
    const userService = inject(UserService);

    const user: any = await userService.loginByToken();
    const loggedIn = user instanceof User && !!user.id;
    return router.parseUrl(loggedIn ? loggedInUrl : anonymousUrl);
  };
}

/**
 * Route guard requiring a logged in user.
 * Anonymous visitors are redirected to the given URL instead of the login page.
 *
 * Usage: { path: 'dashboard', component: ..., canActivate: [loggedInGuard('/recipes')] }
 */
export function loggedInGuard(anonymousUrl: string): CanActivateFn {
  return async () => {
    const router = inject(Router);
    const userService = inject(UserService);

    const user: any = await userService.loginByToken();
    if (user instanceof User && !!user.id) {
      return true;
    }
    return router.parseUrl(anonymousUrl);
  };
}

/**
 * Route guard requiring a logged in user.
 * Anonymous visitors are sent to the login page, and brought back after logging in.
 *
 * Usage: { path: 'new', component: ..., canActivate: [loginRequiredGuard] }
 */
export const loginRequiredGuard: CanActivateFn = async (route, state) => {
  const router = inject(Router);
  const userService = inject(UserService);

  const user: any = await userService.loginByToken();
  if (user instanceof User && !!user.id) {
    return true;
  }
  return router.createUrlTree(['/login'], { queryParams: { redirect: state.url } });
};
