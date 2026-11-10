import { TestBed } from '@angular/core/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { TetraAppComponent } from './app.component';
import { CoreService } from '@tetra/services/core.service';
import { AppService } from '@tetra/services/app.service';
import { UserService } from '@tetra/services/user.service';
import { mockCoreService } from '@tetra/services/core.service.mock';
import { mockAppService } from '@tetra/services/app.service.mock';
import { mockUserService } from '@tetra/services/user.service.mock';

describe('TetraAppComponent', () => {
  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [
        TetraAppComponent,
        RouterTestingModule,
      ],
      providers: [
        { provide: CoreService, useValue: mockCoreService },
        { provide: AppService, useValue: mockAppService },
        { provide: UserService, useValue: mockUserService },
      ]
    }).compileComponents();

    jest.clearAllMocks();
  });

  it('should create', () => {
    const fixture = TestBed.createComponent(TetraAppComponent);
    fixture.detectChanges();
    expect(fixture.componentInstance).toBeTruthy();
  });

  it('should have default config', () => {
    const fixture = TestBed.createComponent(TetraAppComponent);
    expect(fixture.componentInstance.config.name).toBe('Application');
  });

  it('should update config from appService', () => {
    const { of } = require('rxjs');
    mockAppService.getConfig.mockReturnValue(of({ name: 'My App' }));
    const fixture = TestBed.createComponent(TetraAppComponent);
    fixture.detectChanges();
    expect(fixture.componentInstance.config.name).toBe('My App');
  });

  it('should update pageTitle from appService', () => {
    const { of } = require('rxjs');
    mockAppService.getPageTitle.mockReturnValue(of('New Page Title'));
    const fixture = TestBed.createComponent(TetraAppComponent);
    fixture.detectChanges();
    expect(fixture.componentInstance.pageTitle).toBe('New Page Title');
  });

  it('should call userService.logout on logout()', () => {
    const fixture = TestBed.createComponent(TetraAppComponent);
    fixture.componentInstance.logout();
    expect(mockUserService.logout).toHaveBeenCalled();
  });
});
