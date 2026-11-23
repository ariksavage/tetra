import { Injectable, signal } from '@angular/core';

/**
 * '' when the API is answering; 'unreachable' when nothing answers at all (the
 * server, or locally DDEV, isn't running); 'database' when the API answers
 * but its database doesn't.
 */
export type ConnectionProblem = '' | 'unreachable' | 'database';

/**
 * Whether the app can reach its API and database. The connection interceptor
 * reports every API call's outcome here; while there's a problem, this checks
 * again every so often, so the warning clears by itself once it's fixed.
 */
@Injectable({
  providedIn: 'root'
})
export class ConnectionService {
  readonly problem = signal<ConnectionProblem>('');

  /** How often to check again while there's a problem. */
  static readonly RETRY_MS = 15000;

  private retryTimer: ReturnType<typeof setInterval> | null = null;

  report(problem: ConnectionProblem) {
    if (problem === this.problem()) {
      return;
    }
    this.problem.set(problem);
    if (problem && !this.retryTimer && typeof window !== 'undefined') {
      this.retryTimer = setInterval(() => this.check(), ConnectionService.RETRY_MS);
    } else if (!problem && this.retryTimer) {
      clearInterval(this.retryTimer);
      this.retryTimer = null;
    }
  }

  /**
   * Ask the API whether it and its database are up. Uses fetch, not
   * HttpClient, so it doesn't depend on the interceptor that depends on this.
   */
  check(): Promise<ConnectionProblem> {
    return fetch('/core/app/health', { cache: 'no-store' }).then(
      response => response.json().catch(() => null).then(body => {
        const problem = ConnectionService.classify(response.status, body);
        this.report(problem);
        return problem;
      }),
      () => {
        this.report('unreachable');
        return 'unreachable' as ConnectionProblem;
      }
    );
  }

  /**
   * What a response says about the connection. Tetra's own errors carry a
   * code; a gateway error without one comes from a proxy in front of the API
   * (eg. ng serve's, with DDEV stopped), so the API itself didn't answer.
   */
  static classify(status: number, body: any): ConnectionProblem {
    if (status === 0) {
      return 'unreachable';
    }
    if (status === 503 && body?.type === 'Database') {
      return 'database';
    }
    if ([502, 503, 504].includes(status) && !body?.code) {
      return 'unreachable';
    }
    return '';
  }
}
