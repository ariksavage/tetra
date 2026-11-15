import { TestBed } from '@angular/core/testing';
import { PageDataService, providePageDataConverters } from './page-data.service';

/** The tag the page renderer writes, for a path and converter. */
function addTag(path: string, converter: string | null, json: string) {
  const tag = document.createElement('script');
  tag.type = 'application/json';
  tag.id = 'tetra-page-data';
  tag.setAttribute('data-tetra-path', path);
  if (converter) {
    tag.setAttribute('data-tetra-from-schema', converter);
  }
  tag.textContent = json;
  document.head.appendChild(tag);
}

describe('PageDataService', () => {
  const ld = { alias: 'mac-n-cheese', title: 'Mac n Cheese' };
  const recipeFromSchema = (data: any) => data?.alias ? data : null;

  function service(converters: Record<string, any> = { recipeFromSchema }) {
    TestBed.configureTestingModule({ providers: [providePageDataConverters(converters)] });
    return TestBed.inject(PageDataService);
  }

  beforeEach(() => window.history.pushState({}, '', '/recipes/mac-n-cheese'));

  afterEach(() => {
    document.head.querySelectorAll('script[type="application/json"], script[type="application/ld+json"]').forEach(tag => tag.remove());
    TestBed.resetTestingModule();
  });

  it("gives the first page's data, converted, once", () => {
    addTag('/recipes/mac-n-cheese', 'recipeFromSchema', JSON.stringify(ld));
    const pageData = service();
    expect(pageData.take()).toEqual({ alias: 'mac-n-cheese', title: 'Mac n Cheese' });
    expect(pageData.take()).toBeNull();
  });

  it('gives nothing on another page (moving inside the app)', () => {
    addTag('/recipes/mac-n-cheese', 'recipeFromSchema', JSON.stringify(ld));
    const pageData = service();
    window.history.pushState({}, '', '/recipes/pozole-rojo');
    expect(pageData.take()).toBeNull();
  });

  it('treats a trailing slash and an encoded path as the same page', () => {
    window.history.pushState({}, '', '/recipes/mac%20n%20cheese/');
    addTag('/recipes/mac n cheese', 'recipeFromSchema', JSON.stringify(ld));
    expect(service().take()).not.toBeNull();
  });

  it('gives nothing without the renderer, without a converter name, or with one the app lacks', () => {
    expect(service().take()).toBeNull();
    TestBed.resetTestingModule();

    addTag('/recipes/mac-n-cheese', null, JSON.stringify(ld));
    expect(service().take()).toBeNull();
    TestBed.resetTestingModule();
    document.head.querySelectorAll('script[type="application/json"], script[type="application/ld+json"]').forEach(tag => tag.remove());

    const warn = jest.spyOn(console, 'warn').mockImplementation(() => {});
    addTag('/recipes/mac-n-cheese', 'somethingElse', JSON.stringify(ld));
    expect(service().take()).toBeNull();
    expect(warn).toHaveBeenCalled();
    warn.mockRestore();
  });

  it("doesn't read the JSON-LD (that's for search engines)", () => {
    const tag = document.createElement('script');
    tag.type = 'application/ld+json';
    tag.setAttribute('data-tetra-path', '/recipes/mac-n-cheese');
    tag.setAttribute('data-tetra-from-schema', 'recipeFromSchema');
    tag.textContent = JSON.stringify(ld);
    document.head.appendChild(tag);
    expect(service().take()).toBeNull();
  });

  it('gives nothing for broken JSON or a converter that fails', () => {
    addTag('/recipes/mac-n-cheese', 'recipeFromSchema', '{not json');
    expect(service().take()).toBeNull();
    TestBed.resetTestingModule();
    document.head.querySelectorAll('script[type="application/json"], script[type="application/ld+json"]').forEach(tag => tag.remove());

    const warn = jest.spyOn(console, 'warn').mockImplementation(() => {});
    addTag('/recipes/mac-n-cheese', 'recipeFromSchema', JSON.stringify(ld));
    expect(service({ recipeFromSchema: () => { throw new Error('nope'); } }).take()).toBeNull();
    warn.mockRestore();
  });
});
