import { TestBed } from '@angular/core/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { Page } from './page.component';
import { CoreService } from '@tetra/services/core.service';
import { mockCoreService } from '@tetra/services/core.service.mock';

describe('Page', () => {
  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [
        Page,
        RouterTestingModule,
      ],
      providers: [
        { provide: CoreService, useValue: mockCoreService }
      ]
    }).compileComponents();
  });

  it('should create', () => {
    const fixture = TestBed.createComponent(Page);
    fixture.detectChanges();
    expect(fixture.componentInstance).toBeTruthy();
  });
});
