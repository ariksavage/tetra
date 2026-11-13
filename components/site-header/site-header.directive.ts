import { Directive, HostBinding, inject } from '@angular/core';
import { TETRA_CONFIG } from '@tetra/classes/tetra-config';

/**
 * Applies tetra.json's header settings to the app's site header. Import it
 * where the header is (the app layout); it matches the header by its class:
 *
 *   <header class="site-header"> ... </header>
 *
 * "header": { "longTitle": "truncate" } keeps the header to one row, cutting
 * a long title short with an ellipsis; the default, "wrap", lets the menu and
 * icons drop below the title instead. The layout itself is in Tetra's base
 * styles (styles/_base.scss, "The site's header").
 */
@Directive({
  selector: 'header.site-header',
})
export class SiteHeaderDirective {
  private config = inject(TETRA_CONFIG);

  @HostBinding('class.title-truncate') get truncate() {
    return this.config.header.longTitle === 'truncate';
  }
}
