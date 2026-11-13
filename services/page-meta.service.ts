import { DOCUMENT, Injectable, inject } from '@angular/core';
import { Meta } from '@angular/platform-browser';
import { NavigationEnd, NavigationStart, Router } from '@angular/router';
import { combineLatest } from 'rxjs';
import { filter } from 'rxjs/operators';
import { AppService } from '@tetra/services/app.service';
import { TETRA_CONFIG } from '@tetra/classes/tetra-config';

/** What a page says about itself; anything left out is the site's default. */
export interface PageMetaInput {
  description?: string;
  /** A path from the site's root (eg. images/...) or a full URL. */
  image?: string;
}

/**
 * Keeps the head's description, canonical URL and share tags (Open Graph,
 * Twitter) in step with the page while moving around inside the app, so a
 * link copied from any page previews as that page. The title already follows
 * AppService.setPageTitle; the share titles follow it too.
 *
 * - The first page is left as the page renderer (Tetra's PHP) wrote it.
 * - Each later navigation starts from the site's defaults (tetra.json's
 *   seo.description and seo.image); a page then sets its own with set().
 * - The JSON-LD and robots are never touched: crawlers load every URL fresh
 *   from the server, which writes both for that URL.
 *
 * Start it once, in the app's root component or layout: `inject(PageMetaService).start()`.
 */
@Injectable({ providedIn: 'root' })
export class PageMetaService {
  private meta = inject(Meta);
  private document = inject(DOCUMENT);
  private router = inject(Router);
  private app = inject(AppService);
  private tetra = inject(TETRA_CONFIG);

  private started = false;
  private siteName = '';
  private baseUrl = '';
  private pageTitle = '';

  start() {
    if (this.started) {
      return;
    }
    this.started = true;
    // Every navigation after the app's first (the page the server wrote):
    // back to the site's defaults, before the new page sets its own. The
    // router numbers navigations from 1; this may start after the first has
    // begun, so it goes by that number, not by which it sees first.
    this.router.events.pipe(filter((event): event is NavigationStart => event instanceof NavigationStart)).subscribe(event => {
      if (event.id > 1) {
        this.set({});
      }
    });
    // The page's address, once the browser has it: at the start of a
    // navigation, and while the page sets its own, it's still the last one.
    this.router.events.pipe(filter((event): event is NavigationEnd => event instanceof NavigationEnd)).subscribe(event => {
      if (event.id > 1) {
        this.url();
      }
    });
    combineLatest([this.app.getConfig(), this.app.getPageTitle()]).subscribe(([config, title]) => {
      this.siteName = config?.name ?? this.siteName;
      this.baseUrl = /^https?:\/\/[^/\s]+$/i.test(config?.base_url ?? '') ? config.base_url : '';
      this.pageTitle = title ?? '';
      const full = this.pageTitle ? `${this.pageTitle} | ${this.siteName}` : this.siteName;
      this.tag('property', 'og:title', full);
      this.tag('name', 'twitter:title', full);
      this.tag('property', 'og:site_name', this.siteName);
    });
  }

  /**
   * The current page's description and image (the site's defaults for what's
   * left out). The URL tags follow the address by themselves.
   */
  set(page: PageMetaInput) {
    const description = page.description || this.tetra.seo.description;
    const image = this.absolute(page.image || this.tetra.seo.image);
    this.tag('name', 'description', description);
    this.tag('property', 'og:description', description);
    this.tag('name', 'twitter:description', description);
    this.tag('property', 'og:image', image);
    this.tag('name', 'twitter:image', image);
    this.tag('name', 'twitter:card', image ? 'summary_large_image' : 'summary');
  }

  /** The canonical URL and og:url: the browser's current address, on the site's address. */
  private url() {
    const url = this.absolute(this.document.location?.pathname ?? '/');
    this.tag('property', 'og:url', url);
    this.canonical(url);
  }

  /** Text for a description: no tags, single spaces, at most 200 characters, cut at a word. */
  static plain(html: string, max = 200): string {
    const div = typeof document !== 'undefined' ? document.createElement('div') : null;
    let text = html || '';
    if (div) {
      div.innerHTML = text;
      text = div.textContent || '';
    }
    text = text.replace(/\s+/g, ' ').trim();
    if (text.length <= max) {
      return text;
    }
    const cut = text.slice(0, max - 1);
    const space = cut.lastIndexOf(' ');
    return (space > max * 0.6 ? cut.slice(0, space) : cut).replace(/[ ,.;:]+$/, '') + '…';
  }

  /** A full URL on the site's address (seo.baseUrl), else this page's own. */
  private absolute(pathOrUrl: string): string {
    if (!pathOrUrl || /^https?:\/\//i.test(pathOrUrl)) {
      return pathOrUrl || '';
    }
    const base = this.baseUrl || this.document.location?.origin || '';
    return base + '/' + pathOrUrl.replace(/^\/+/, '');
  }

  /** Set a meta tag's content, adding the tag if it isn't there; remove it for ''. */
  private tag(attr: 'name' | 'property', key: string, content: string) {
    const selector = `${attr}="${key}"`;
    if (!content) {
      this.meta.removeTag(selector);
      return;
    }
    if (this.meta.getTag(selector)) {
      this.meta.updateTag({ [attr]: key, content } as any, selector);
    } else {
      this.meta.addTag({ [attr]: key, content } as any);
    }
  }

  private canonical(url: string) {
    let link = this.document.head.querySelector('link[rel="canonical"]') as HTMLLinkElement | null;
    if (!link) {
      link = this.document.createElement('link');
      link.rel = 'canonical';
      this.document.head.appendChild(link);
    }
    link.href = url;
  }
}
