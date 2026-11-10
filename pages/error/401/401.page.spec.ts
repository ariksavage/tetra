import { TestBed } from '@angular/core/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { AccessDeniedPageComponent } from './401.page';
import { CoreService } from '@tetra/services/core.service';
import { AppService } from '@tetra/services/app.service';
import { UserService } from '@tetra/services/user.service';
import { mockCoreService } from '@tetra/services/core.service.mock';
import { mockAppService } from '@tetra/services/app.service.mock';
import { mockUserService } from '@tetra/services/user.service.mock';
import { overrideRootServices } from '@tetra/testing/override-providers';

describe('AccessDeniedPageComponent', () => {
  beforeEach(async () => {
    await overrideRootServices(
      TestBed.configureTestingModule({
        imports: [
          AccessDeniedPageComponent,
          RouterTestingModule,
        ],
        providers: [
          { provide: CoreService, useValue: mockCoreService },
          { provide: AppService, useValue: mockAppService },
          { provide: UserService, useValue: mockUserService },
        ]
      })
    ).compileComponents();
  });

  it('should create', () => {
    const fixture = TestBed.createComponent(AccessDeniedPageComponent);
    fixture.detectChanges();
    expect(fixture.componentInstance).toBeTruthy();
  });
});
