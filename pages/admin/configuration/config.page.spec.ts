import { TestBed } from '@angular/core/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { ConfigPageComponent } from './config.page';
import { overrideRootServices, rootServiceProviders } from '@tetra/testing/override-providers';
import { mockCoreService } from '@tetra/services/core.service.mock';
import { mockAppService } from '@tetra/services/app.service.mock';

const rows = [
  { id: 1, type: 'application', key: 'name', label: 'Application Name', value: 'Thyme', value_type: 'text' },
  { id: 2, type: 'application', key: 'session_expires_days', label: 'Session', value: 1, value_type: 'number' },
  { id: 3, type: 'features', key: 'flag', label: 'Flag', value: false, value_type: 'boolean' },
  { id: 4, type: 'features', key: 'opts', label: 'Options', value: { a: 1 }, value_type: 'object' },
  { id: 5, type: 'features', key: 'future', label: 'Future', value: 'x', value_type: 'something_new' },
];

describe('ConfigPageComponent', () => {
  let page: ConfigPageComponent;

  beforeEach(async () => {
    await overrideRootServices(
      TestBed.configureTestingModule({
        imports: [ConfigPageComponent, RouterTestingModule],
        providers: [...rootServiceProviders],
      })
    ).compileComponents();
    jest.clearAllMocks();
    page = TestBed.createComponent(ConfigPageComponent).componentInstance;
    page.setConfig(rows.map((r) => ({ ...r })));
  });

  it('groups rows by type', () => {
    expect(page.groups.map((g) => g.type)).toEqual(['application', 'features']);
    expect(page.groups[1].items.length).toBe(3);
  });

  it('falls back to a text field for unknown value types', () => {
    expect(page.fieldFor('boolean')).toBe('checkbox');
    expect(page.fieldFor('longtext')).toBe('textarea');
    expect(page.fieldFor('something_new')).toBe('text');
  });

  it('edits objects as JSON text', () => {
    const opts = page.config.find((c) => c.key === 'opts')!;
    expect(JSON.parse(opts.value)).toEqual({ a: 1 });
  });

  it('only posts changed values', async () => {
    expect(page.canSave()).toBe(false);
    page.config.find((c) => c.key === 'name')!.value = 'Thyme 2';
    page.config.find((c) => c.key === 'opts')!.value = '{"a": 2}';
    const payload = { config: [{ id: 1, value: 'Thyme 2' }, { id: 4, value: { a: 2 } }] };
    expect(page.savePayload()).toEqual(payload);

    mockCoreService.post.mockResolvedValueOnce({ config: rows });
    await page.save();
    expect(mockCoreService.post).toHaveBeenCalledWith('config', 'index', payload);
    expect(mockAppService.fetchAppConfig).toHaveBeenCalled();
    expect(page.canSave()).toBe(false);
  });

  it('blocks saving invalid values', () => {
    page.config.find((c) => c.key === 'session_expires_days')!.value = 'abc';
    expect(page.canSave()).toBe(false);
    page.config.find((c) => c.key === 'session_expires_days')!.value = 5;
    page.config.find((c) => c.key === 'opts')!.value = '{bad';
    expect(page.canSave()).toBe(false);
  });
});
