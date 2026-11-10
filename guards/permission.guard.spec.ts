import { TestBed } from '@angular/core/testing';
import { Router, UrlTree, provideRouter } from '@angular/router';
import { UserService } from '@tetra/services/user.service';
import { User } from '@tetra/classes/user';
import { mockUserService } from '@tetra/services/user.service.mock';
import { permissionGuard } from './permission.guard';

describe('permissionGuard', () => {
  const run = () => TestBed.runInInjectionContext(
    () => permissionGuard('config', 'EDIT')({} as any, { url: '/admin/config' } as any)
  ) as Promise<boolean | UrlTree>;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideRouter([]), { provide: UserService, useValue: mockUserService }],
    });
    jest.clearAllMocks();
  });

  it('redirects to login when there is no user', async () => {
    mockUserService.loginByToken.mockResolvedValueOnce('no token found');
    const result = await run();
    expect(TestBed.inject(Router).serializeUrl(result as UrlTree)).toBe('/login?redirect=%2Fadmin%2Fconfig');
  });

  it('redirects to 401 without the permission', async () => {
    mockUserService.loginByToken.mockResolvedValueOnce(new User({ id: 5, roles: [{ permissions: { recipes: { VIEW: {} } } }] }));
    const result = await run();
    expect(TestBed.inject(Router).serializeUrl(result as UrlTree)).toBe('/error/401');
  });

  it('allows users with the permission', async () => {
    mockUserService.loginByToken.mockResolvedValueOnce(new User({ id: 1, roles: [{ permissions: { config: { '*': {} } } }] }));
    expect(await run()).toBe(true);
  });
});
