import { TestBed } from '@angular/core/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { InstallPageComponent } from './install.page';
import { CoreService } from '@tetra/services/core.service';
import { mockCoreService } from '@tetra/services/core.service.mock';

describe('InstallPageComponent', () => {
  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [
        InstallPageComponent,
        RouterTestingModule,
      ],
      providers: [
        { provide: CoreService, useValue: mockCoreService }
      ]
    }).compileComponents();
  });

  it('should create', () => {
    const fixture = TestBed.createComponent(InstallPageComponent);
    fixture.detectChanges();
    expect(fixture.componentInstance).toBeTruthy();
  });
});
