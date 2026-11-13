import { TestBed } from '@angular/core/testing';
import { NavigationEnd, NavigationStart, Router } from '@angular/router';
import { BehaviorSubject, Subject } from 'rxjs';
import { PageMetaService } from './page-meta.service';
import { AppService } from '@tetra/services/app.service';
import { provideTetraConfig } from '@tetra/classes/tetra-config';

describe('PageMetaService', () => {
  const events = new Subject<any>();
  const config = new BehaviorSubject<any>({ name: 'Thyme', base_url: 'https://thyme.example' });
  const title = new BehaviorSubject<string>('');

  const head = (selector: string) => document.head.querySelector(selector)?.getAttribute('content') ?? null;

  beforeEach(() => {
    document.head.querySelectorAll('meta, link[rel="canonical"]').forEach(tag => tag.remove());
    window.history.pushState({}, '', '/recipes/mac-n-cheese');
    TestBed.configureTestingModule({
      providers: [
        provideTetraConfig({ seo: { description: 'A recipe collection.', image: '/favicon/icon.png' } }),
        { provide: Router, useValue: { events } },
        { provide: AppService, useValue: { getConfig: () => config, getPageTitle: () => title } },
      ],
    });
  });

  afterEach(() => TestBed.resetTestingModule());

  it("sets a page's description and image, and the canonical URL once the navigation ends, on the site's address", () => {
    const meta = TestBed.inject(PageMetaService);
    meta.start();
    meta.set({ description: 'Creamy and quick.', image: 'images/recipes/00001/mac.webp' });
    events.next(new NavigationEnd(2, '/recipes/mac-n-cheese', '/recipes/mac-n-cheese'));
    expect(head('meta[name="description"]')).toBe('Creamy and quick.');
    expect(head('meta[property="og:description"]')).toBe('Creamy and quick.');
    expect(head('meta[property="og:image"]')).toBe('https://thyme.example/images/recipes/00001/mac.webp');
    expect(head('meta[name="twitter:card"]')).toBe('summary_large_image');
    expect(head('meta[property="og:url"]')).toBe('https://thyme.example/recipes/mac-n-cheese');
    expect(document.head.querySelector('link[rel="canonical"]')?.getAttribute('href')).toBe('https://thyme.example/recipes/mac-n-cheese');
  });

  it('follows the page title for the share titles', () => {
    TestBed.inject(PageMetaService).start();
    title.next('Mac n Cheese');
    expect(head('meta[property="og:title"]')).toBe('Mac n Cheese | Thyme');
    expect(head('meta[name="twitter:title"]')).toBe('Mac n Cheese | Thyme');
    expect(head('meta[property="og:site_name"]')).toBe('Thyme');
  });

  it("leaves the first page as the server wrote it, then resets to the site's defaults on each navigation", () => {
    const meta = TestBed.inject(PageMetaService);
    const tag = document.createElement('meta');
    tag.name = 'description';
    tag.content = 'Written by the server.';
    document.head.appendChild(tag);
    // Started after the first navigation began, as when a page's layout starts it.
    meta.start();

    events.next(new NavigationStart(1, '/recipes/mac-n-cheese'));
    expect(head('meta[name="description"]')).toBe('Written by the server.');

    window.history.pushState({}, '', '/recipes');
    events.next(new NavigationStart(2, '/recipes'));
    expect(head('meta[name="description"]')).toBe('A recipe collection.');
    expect(head('meta[property="og:image"]')).toBe('https://thyme.example/favicon/icon.png');
  });

  it('makes plain text for a description', () => {
    expect(PageMetaService.plain('<p>Quick <b>and</b>   easy.</p>')).toBe('Quick and easy.');
    expect(PageMetaService.plain('word '.repeat(100), 30)).toMatch(/^(word ){4}word…$/);
  });

  it('takes the URL tags from the address the navigation ended on, not the one it started from', () => {
    const meta = TestBed.inject(PageMetaService);
    meta.start();
    events.next(new NavigationStart(3, '/'));
    // A redirect: "/" ends on /dashboard.
    window.history.pushState({}, '', '/dashboard');
    events.next(new NavigationEnd(3, '/', '/dashboard'));
    expect(document.head.querySelector('link[rel="canonical"]')?.getAttribute('href')).toBe('https://thyme.example/dashboard');
    expect(head('meta[property="og:url"]')).toBe('https://thyme.example/dashboard');
  });
});
