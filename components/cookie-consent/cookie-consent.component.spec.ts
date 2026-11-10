import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CookieConsentComponent } from './cookie-consent.component';
import { ConsentService } from '@tetra/services/consent.service';
import { CookiesService } from '@tetra/services/cookies.service';

describe('CookieConsentComponent', () => {
  let fixture: ComponentFixture<CookieConsentComponent>;
  let consent: ConsentService;
  let cookies: CookiesService;

  const clearCookies = () => {
    document.cookie.split(';').forEach((cookie) => {
      const name = cookie.split('=')[0].trim();
      if (name) {
        document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/';
      }
    });
  };

  const create = async () => {
    await TestBed.configureTestingModule({
      imports: [CookieConsentComponent]
    }).compileComponents();
    consent = TestBed.inject(ConsentService);
    cookies = TestBed.inject(CookiesService);
    fixture = TestBed.createComponent(CookieConsentComponent);
    fixture.detectChanges();
  };

  const banner = () => fixture.nativeElement.querySelector('.consent-banner');

  beforeEach(() => {
    clearCookies();
  });

  it('asks a new visitor for consent and sets no optional cookies', async () => {
    await create();
    expect(banner()).toBeTruthy();
    expect(consent.allowed()).toBe(false);
    expect(consent.setOptional('last_recipe', 'soup')).toBe(false);
    expect(cookies.read('last_recipe')).toBe('');
  });

  it('allows optional cookies after accepting', async () => {
    await create();
    fixture.nativeElement.querySelector('.consent-accept').click();
    fixture.detectChanges();
    expect(banner()).toBeFalsy();
    expect(consent.status()).toBe('accepted');
    expect(consent.setOptional('last_recipe', 'soup')).toBe(true);
    expect(cookies.read('last_recipe')).toBe('soup');
  });

  it('removes optional cookies when declined', async () => {
    document.cookie = 'last_recipe=soup;path=/';
    await create();
    consent.optionalCookies = ['last_recipe'];
    fixture.nativeElement.querySelector('.consent-decline').click();
    fixture.detectChanges();
    expect(banner()).toBeFalsy();
    expect(consent.status()).toBe('declined');
    expect(cookies.read('last_recipe')).toBe('');
    expect(consent.setOptional('last_recipe', 'soup')).toBe(false);
  });

  it('treats no answer the same as declining', async () => {
    document.cookie = 'last_recipe=soup;path=/';
    await create();
    consent.optionalCookies = ['last_recipe'];
    expect(consent.status()).toBeNull();
    expect(banner()).toBeTruthy();
    expect(cookies.read('last_recipe')).toBe('');
    expect(consent.setOptional('last_recipe', 'soup')).toBe(false);
  });

  it('keeps optional cookies for a visitor who accepted', async () => {
    document.cookie = 'cookie_consent=accepted;path=/';
    document.cookie = 'last_recipe=soup;path=/';
    await create();
    consent.optionalCookies = ['last_recipe'];
    expect(cookies.read('last_recipe')).toBe('soup');
  });

  it('does not ask again once a choice is stored, until reviewed', async () => {
    document.cookie = 'cookie_consent=declined;path=/';
    await create();
    expect(banner()).toBeFalsy();
    consent.review();
    fixture.detectChanges();
    expect(banner()).toBeTruthy();
  });
});
