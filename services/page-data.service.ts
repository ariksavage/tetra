import { DOCUMENT, Injectable, InjectionToken, Provider, inject } from '@angular/core';

/**
 * Turns a page's data (as the page renderer wrote it, eg. the record as the
 * API sends it) into the app's own model, eg. Thyme's Recipe. Return null if
 * the data isn't usable; the page then calls the API as usual.
 */
export type PageDataConverter<T = unknown> = (data: any) => T | null;

/** The app's converters, by the name the route map gives them ("fromSchema"). */
export const PAGE_DATA_CONVERTERS = new InjectionToken<Record<string, PageDataConverter>>('PAGE_DATA_CONVERTERS', {
  factory: () => ({})
});

/**
 * Provide the app's converters, in the app config:
 * `providePageDataConverters({ recipeFromSchema })`.
 */
export function providePageDataConverters(converters: Record<string, PageDataConverter>): Provider {
  return { provide: PAGE_DATA_CONVERTERS, useValue: converters };
}

/** The first page's data, as the page renderer wrote it. */
interface Entry {
  path: string;
  converter: string;
  data: any;
}

/**
 * Page data first, API second.
 *
 * The page renderer (Tetra's PHP) writes the first page's data into the page
 * as plain JSON (<script type="application/json" id="tetra-page-data">),
 * beside the JSON-LD it writes for search engines, and marks it with the path
 * it was written for and the route's converter (data-tetra-path,
 * data-tetra-from-schema). It's a separate block so that structured-data
 * parsers don't read the app's data as part of the JSON-LD. The page that
 * path opens asks here before calling the API:
 *
 *   const recipe = this.pageData.take<Recipe>();
 *   if (recipe) { show it } else { call the API }
 *
 * It's only for the page the visit started on, and only once: moving to
 * another page inside the app always calls the API, and the block in the
 * page is never changed. Without the block (a page the renderer didn't write,
 * eg. under ng serve), or with a converter the app doesn't provide, take()
 * gives null and the page calls the API as it always has.
 */
@Injectable({ providedIn: 'root' })
export class PageDataService {
  private document = inject(DOCUMENT);
  private converters = inject(PAGE_DATA_CONVERTERS);
  private entry: Entry | null = this.read();

  /**
   * The first page's data as the app's model, if this is still that page;
   * null otherwise. Gives it out once.
   */
  take<T>(): T | null {
    const entry = this.entry;
    this.entry = null;
    if (!entry || normalize(entry.path) !== normalize(this.document.location?.pathname ?? '')) {
      return null;
    }
    const convert = this.converters[entry.converter];
    if (!convert) {
      console.warn(`Page data: no converter named "${entry.converter}"; calling the API instead.`);
      return null;
    }
    try {
      return (convert(entry.data) as T) ?? null;
    } catch (e) {
      console.warn('Page data: the converter failed; calling the API instead.', e);
      return null;
    }
  }

  /** The renderer's page-data block, if it has a converter named; read once, when the app starts. */
  private read(): Entry | null {
    const tag = this.document.querySelector('script#tetra-page-data[type="application/json"]');
    const path = tag?.getAttribute('data-tetra-path');
    const converter = tag?.getAttribute('data-tetra-from-schema');
    if (!tag || !path || !converter) {
      return null;
    }
    try {
      return { path, converter, data: JSON.parse(tag.textContent || '') };
    } catch {
      return null;
    }
  }
}

/** "/recipes/x/" and "/recipes/x" are the same page; so are encoded and plain. */
function normalize(path: string): string {
  let decoded = path;
  try {
    decoded = decodeURIComponent(path);
  } catch {
    // Leave a malformed path as it is.
  }
  return decoded.replace(/\/+$/, '') || '/';
}
