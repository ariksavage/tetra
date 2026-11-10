import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of } from 'rxjs';

import { PreferenceToggleComponent } from './preference-toggle.component';
import { PreferencesService } from '@tetra/services/preferences.service';
import { CoreService } from '@tetra/services/core.service';
import { UserService } from '@tetra/services/user.service';

describe('PreferenceToggleComponent', () => {
  let fixture: ComponentFixture<PreferenceToggleComponent>;

  const create = async () => {
    await TestBed.configureTestingModule({
      imports: [PreferenceToggleComponent],
      providers: [
        { provide: CoreService, useValue: { get: jest.fn().mockResolvedValue({}), post: jest.fn().mockResolvedValue({}) } },
        { provide: UserService, useValue: { getUser: () => of(null) } },
      ]
    }).compileComponents();
    fixture = TestBed.createComponent(PreferenceToggleComponent);
    fixture.componentRef.setInput('key', 'start_timers_on_click');
    fixture.componentRef.setInput('label', 'Start timers on click');
    fixture.detectChanges();
  };

  const button = () => fixture.nativeElement.querySelector('button');

  beforeEach(() => {
    window.localStorage.clear();
  });

  it('is off until chosen', async () => {
    await create();
    expect(button().getAttribute('aria-checked')).toBe('false');
    expect(button().textContent).toContain('Start timers on click: Off');
  });

  it('saves the choice as a preference', async () => {
    await create();
    button().click();
    fixture.detectChanges();
    expect(button().getAttribute('aria-checked')).toBe('true');
    expect(TestBed.inject(PreferencesService).get('start_timers_on_click')).toBe(true);
  });

  it('reads a saved preference on load', async () => {
    window.localStorage.setItem('preferences', JSON.stringify({ start_timers_on_click: true }));
    await create();
    expect(button().getAttribute('aria-checked')).toBe('true');
  });
});
