import { TestBed } from '@angular/core/testing';
import { Router, UrlTree } from '@angular/router';
import { RouterTestingModule } from '@angular/router/testing';
import { landingGuard, loggedInGuard, loginRequiredGuard } from './landing.guard';
import { UserService } from '@tetra/services/user.service';
import { User } from '@tetra/classes/user';

describe('landingGuard', () => {
  const userService = { loginByToken: jest.fn() };

  const run = async () => {
    const guard = landingGuard('/dashboard', '/recipes');
    const result = await TestBed.runInInjectionContext(() => guard({} as any, {} as any));
    return TestBed.inject(Router).serializeUrl(result as UrlTree);
  };

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [RouterTestingModule],
      providers: [{ provide: UserService, useValue: userService }]
    });
  });

  it('sends a logged in user to the dashboard', async () => {
    userService.loginByToken.mockResolvedValue(new User({ id: 5 }));
    expect(await run()).toBe('/dashboard');
  });

  it('sends an anonymous visitor to the recipe list', async () => {
    userService.loginByToken.mockResolvedValue('no token found');
    expect(await run()).toBe('/recipes');
  });

  it('treats an expired session as anonymous', async () => {
    userService.loginByToken.mockResolvedValue(false);
    expect(await run()).toBe('/recipes');
  });

  describe('loggedInGuard', () => {
    const runGate = () => TestBed.runInInjectionContext(() => loggedInGuard('/recipes')({} as any, {} as any));

    it('lets a logged in user through', async () => {
      userService.loginByToken.mockResolvedValue(new User({ id: 5 }));
      expect(await runGate()).toBe(true);
    });

    it('redirects an anonymous visitor', async () => {
      userService.loginByToken.mockResolvedValue('no token found');
      const result = await runGate();
      expect(TestBed.inject(Router).serializeUrl(result as UrlTree)).toBe('/recipes');
    });
  });

  describe('loginRequiredGuard', () => {
    const runGate = () => TestBed.runInInjectionContext(() => loginRequiredGuard({} as any, { url: '/recipes/new' } as any));

    it('lets a logged in user through', async () => {
      userService.loginByToken.mockResolvedValue(new User({ id: 5 }));
      expect(await runGate()).toBe(true);
    });

    it('sends an anonymous visitor to login, remembering where they were going', async () => {
      userService.loginByToken.mockResolvedValue(false);
      const result = await runGate();
      expect(TestBed.inject(Router).serializeUrl(result as UrlTree)).toBe('/login?redirect=%2Frecipes%2Fnew');
    });
  });
});
