import { TestBed } from '@angular/core/testing';
import { TETRA_CONFIG, provideTetraConfig, tetraDefaults, validateTetraConfig } from './tetra-config';

describe('Tetra Config', () => {
  it('reads every default from the schema', () => {
    const defaults = tetraDefaults();
    expect(defaults.app.name).toBe('Tetra');
    expect(defaults.app.copyright).toEqual({ before: '', after: '' });
    expect(defaults.sessions).toEqual({ expiresDays: 1, passwordResetExpiresDays: 10 });
    expect(defaults.siteMenu.collapse).toBe('auto');
    expect(defaults.header.longTitle).toBe('wrap');
    expect(defaults.seo).toEqual({ indexable: true, baseUrl: '', routes: 'src/page-routes.json', description: '', image: '', robots: { mode: 'add', rules: [] } });
    expect(defaults.$schema).toBeUndefined();
  });

  it('accepts a valid tetra.json', () => {
    expect(validateTetraConfig({
      $schema: './tetra/tetra.schema.json',
      app: { name: 'Thyme', copyright: { after: 'Arik Savage' } },
      sessions: { expiresDays: 100 },
      siteMenu: { collapse: 'always' },
      header: { longTitle: 'truncate' },
    })).toEqual([]);
  });

  it('lists unknown settings and values the schema does not allow', () => {
    const errors = validateTetraConfig({
      siteMenu: { collapse: 'sometimes' },
      sessions: { expiresDays: 0 },
      app: { name: 3 },
      apiKey: 'secret',
      seo: { robots: { rules: ['User-agent: GPTBot', 3] } },
    });
    expect(errors).toEqual([
      'siteMenu.collapse: "sometimes" isn\'t one of "auto", "always", "never"',
      'sessions.expiresDays: must be at least 1',
      'app.name: expected string, got integer',
      'apiKey: not a Tetra setting (see tetra.schema.json)',
      'seo.robots.rules[1]: expected string, got integer',
    ]);
  });

  it('is the defaults when the app provides nothing', () => {
    TestBed.configureTestingModule({});
    expect(TestBed.inject(TETRA_CONFIG).siteMenu.collapse).toBe('auto');
  });

  it("lays the app's settings over the defaults, keeping a default where a value is invalid", () => {
    const error = jest.spyOn(console, 'error').mockImplementation(() => {});
    TestBed.configureTestingModule({
      providers: [provideTetraConfig({ siteMenu: { collapse: 'always' }, header: { longTitle: 'sideways' } })],
    });
    const config = TestBed.inject(TETRA_CONFIG);
    expect(config.siteMenu.collapse).toBe('always');
    expect(config.header.longTitle).toBe('wrap');
    expect(config.app.name).toBe('Tetra');
    expect(error).toHaveBeenCalled();
    error.mockRestore();
  });
});
