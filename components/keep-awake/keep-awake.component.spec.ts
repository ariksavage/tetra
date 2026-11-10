import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of } from 'rxjs';

import { KeepAwakeComponent } from './keep-awake.component';
import { PreferencesService } from '@tetra/services/preferences.service';
import { WakeLockService } from '@tetra/services/wake-lock.service';
import { CoreService } from '@tetra/services/core.service';
import { UserService } from '@tetra/services/user.service';
import { User } from '@tetra/classes/user';

describe('KeepAwakeComponent', () => {
  let fixture: ComponentFixture<KeepAwakeComponent>;
  let core: { get: jest.Mock, post: jest.Mock };
  let wakeLock: { supported: jest.Mock, enable: jest.Mock, disable: jest.Mock };

  const create = async (user: User | null, supported: boolean = true) => {
    core = {
      get: jest.fn().mockResolvedValue({ preferences: {} }),
      post: jest.fn().mockResolvedValue({ preferences: {} }),
    };
    wakeLock = { supported: jest.fn().mockReturnValue(supported), enable: jest.fn(), disable: jest.fn() };
    await TestBed.configureTestingModule({
      imports: [KeepAwakeComponent],
      providers: [
        { provide: CoreService, useValue: core },
        { provide: UserService, useValue: { getUser: () => of(user) } },
        { provide: WakeLockService, useValue: wakeLock },
      ]
    }).compileComponents();
    fixture = TestBed.createComponent(KeepAwakeComponent);
    fixture.detectChanges();
  };

  const button = () => fixture.nativeElement.querySelector('.keep-awake-toggle');

  beforeEach(() => {
    window.localStorage.clear();
  });

  it('is off by default', async () => {
    await create(null);
    expect(button().getAttribute('aria-checked')).toBe('false');
    expect(wakeLock.enable).not.toHaveBeenCalled();
  });

  it('is hidden when the browser cannot keep the screen on', async () => {
    await create(null, false);
    expect(button()).toBeFalsy();
  });

  it('keeps an anonymous choice in the browser and applies it', async () => {
    await create(null);
    button().click();
    fixture.detectChanges();
    expect(button().getAttribute('aria-checked')).toBe('true');
    expect(wakeLock.enable).toHaveBeenCalled();
    expect(core.post).not.toHaveBeenCalled();
    expect(JSON.parse(window.localStorage.getItem('preferences') || '{}').keep_screen_on).toBe(true);
  });

  it('applies a saved preference on load', async () => {
    window.localStorage.setItem('preferences', JSON.stringify({ keep_screen_on: true }));
    await create(null);
    expect(button().getAttribute('aria-checked')).toBe('true');
    expect(wakeLock.enable).toHaveBeenCalled();
  });

  it('saves a logged in user\'s choice to their account', async () => {
    await create(new User({ id: 5 }));
    expect(core.get).toHaveBeenCalledWith('users', 'preferences');
    button().click();
    expect(core.post).toHaveBeenCalledWith('users', 'preferences', { preferences: { keep_screen_on: true } }, false);
  });

  it('uses the account\'s preference over the browser\'s', async () => {
    window.localStorage.setItem('preferences', JSON.stringify({ keep_screen_on: false }));
    core = { get: jest.fn(), post: jest.fn() } as any;
    await create(new User({ id: 5 }));
    // create() replaced core; answer its fetch with the account's value
    const prefs = TestBed.inject(PreferencesService) as any;
    prefs.apply({ keep_screen_on: true });
    fixture.detectChanges();
    expect(button().getAttribute('aria-checked')).toBe('true');
    expect(wakeLock.enable).toHaveBeenCalled();
  });
});
