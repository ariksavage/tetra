import { inject } from '@angular/core';
import { HttpErrorResponse, HttpInterceptorFn, HttpResponse } from '@angular/common/http';
import { tap } from 'rxjs';

import { ConnectionService } from '@tetra/services/connection.service';

/**
 * Reports each API call's outcome to the ConnectionService, which warns when
 * the API or its database can't be reached. Add it to the app's HTTP client:
 *
 *   provideHttpClient(withInterceptors([connectionInterceptor]))
 */
export const connectionInterceptor: HttpInterceptorFn = (request, next) => {
  if (!request.url.includes('/core/')) {
    return next(request);
  }
  const connection = inject(ConnectionService);
  return next(request).pipe(tap({
    next: event => {
      if (event instanceof HttpResponse) {
        connection.report('');
      }
    },
    error: (error: unknown) => {
      if (error instanceof HttpErrorResponse) {
        connection.report(ConnectionService.classify(error.status, error.error));
      }
    },
  }));
};
