import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { UserService } from '@tetra/services/user.service';
import { User } from '@tetra/classes/user';

/**
 * Route guard requiring a permission.
 *
 * - Not logged in: redirect to /login?redirect=<url>
 * - Logged in without the permission: redirect to /error/401
 *
 * The API enforces the same permission; this only keeps
 * users off pages they can't use.
 *
 * Usage: canActivate: [permissionGuard('config', 'EDIT')]
 */
export function permissionGuard(dimension: string, action: string = ''): CanActivateFn {
  return async (route, state) => {
    const router = inject(Router);
    const userService = inject(UserService);

    const user: any = await userService.loginByToken();
    if (!(user instanceof User) || !user.id) {
      return router.createUrlTree(['/login'], { queryParams: { redirect: state.url } });
    }
    if (!user.hasPermission(dimension, action)) {
      return router.createUrlTree(['/error/401']);
    }
    return true;
  };
}
