import { Component, ChangeDetectionStrategy, ChangeDetectorRef, DestroyRef, ElementRef, HostBinding, HostListener, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NavigationStart, Router } from '@angular/router';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { filter } from 'rxjs/operators';

let nextId = 0;

/**
 * A site's main menu that collapses to a hamburger on small screens.
 *
 * Wrap the menu's list in it:
 *
 *   <nav class="site-menu" aria-label="Main">
 *     <ul>
 *       <li><a routerLink="/dashboard">Dashboard</a></li>
 *       ...
 *     </ul>
 *   </nav>
 *
 * Where the menu button sits in the header is up to the site: the nav has
 * the class "collapsed" while it's showing (eg. `.main-nav.collapsed { order: 1 }`).
 *
 * Wide screens show the list as the site styles it. Below the breakpoint the
 * list is hidden behind a menu button; opening it slides the list in from
 * the left, full screen, and the button becomes an X in the same place.
 * Following a link, pressing Escape, the X, or widening the window past the
 * breakpoint closes it.
 *
 * Theme (CSS variables, on :root or on the nav):
 *   --site-menu-breakpoint   collapses below this width (default 800px; read once, when it loads)
 *   --site-menu-background   the open menu's background (default --body, the page's)
 *   --site-menu-text         its text colour (default --default-text)
 *   --site-menu-font-size    its links' size (default 1.5em)
 *   --site-menu-align        its links' alignment: center (default), start or end
 *   --site-menu-padding-x    space at its sides (default 1.5rem)
 *   --site-menu-speed        how long it takes to slide in (default .3s; none with reduced motion)
 * In SCSS, set $site-menu-breakpoint before using tetra/styles/theme.
 */
@Component({
  selector: 'nav.site-menu',
  imports: [CommonModule],
  templateUrl: './site-menu.component.html',
  changeDetection: ChangeDetectionStrategy.Eager,
  styleUrl: './site-menu.component.scss'
})
export class SiteMenuComponent implements OnInit {
  private host = inject(ElementRef<HTMLElement>);
  private router = inject(Router);
  private destroyRef = inject(DestroyRef);
  private cdr = inject(ChangeDetectorRef);

  readonly panelId = `site-menu-${++nextId}`;

  /** Below the breakpoint: the list sits behind the menu button. */
  @HostBinding('class.collapsed') collapsed = false;

  @HostBinding('class.open') open = false;

  /**
   * Slides only when opened or closed, not when a resize collapses the
   * menu (it would show the list sliding away).
   */
  @HostBinding('class.animate') animate = false;

  private media?: MediaQueryList;

  ngOnInit() {
    const breakpoint = getComputedStyle(this.host.nativeElement).getPropertyValue('--site-menu-breakpoint').trim() || '800px';
    this.media = window.matchMedia(`(width < ${breakpoint})`);
    const update = () => {
      this.animate = false;
      this.collapsed = !!this.media?.matches;
      if (!this.collapsed) {
        this.close(false);
      }
      this.cdr.markForCheck();
    };
    update();
    this.media.addEventListener('change', update);

    // However a page is left (a link here, or anything else), the menu closes.
    this.router.events.pipe(
      filter(event => event instanceof NavigationStart),
      takeUntilDestroyed(this.destroyRef),
    ).subscribe(() => this.close(false));

    this.destroyRef.onDestroy(() => {
      this.media?.removeEventListener('change', update);
      this.lockScroll(false);
    });
  }

  toggle() {
    if (this.open) {
      this.close();
    } else {
      this.show();
    }
  }

  show() {
    if (!this.collapsed) {
      return;
    }
    // The list starts below the button, so the X stays clear above it.
    const button = this.host.nativeElement.querySelector('.site-menu-toggle') as HTMLElement | null;
    const top = button ? Math.ceil(button.getBoundingClientRect().bottom) : 0;
    this.host.nativeElement.style.setProperty('--site-menu-top', `${top}px`);
    this.animate = true;
    this.open = true;
    this.lockScroll(true);
  }

  /** @param refocus Put focus back on the menu button (when closed from the keyboard or the X). */
  close(refocus: boolean = true) {
    if (!this.open) {
      return;
    }
    this.open = false;
    this.lockScroll(false);
    this.cdr.markForCheck();
    if (refocus) {
      (this.host.nativeElement.querySelector('.site-menu-toggle') as HTMLElement | null)?.focus();
    }
  }

  /** A link in the menu: close it as the page changes. */
  onPanelClick(event: MouseEvent) {
    if ((event.target as Element | null)?.closest('a')) {
      this.close(false);
    }
  }

  @HostListener('document:keydown.escape')
  onEscape() {
    this.close();
  }

  /** The page behind doesn't scroll while the menu covers it. */
  private lockScroll(lock: boolean) {
    document.documentElement.classList.toggle('site-menu-open', lock);
    document.documentElement.style.overflow = lock ? 'hidden' : '';
  }
}
