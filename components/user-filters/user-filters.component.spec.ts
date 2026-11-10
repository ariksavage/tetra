import { TestBed } from '@angular/core/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { UserFiltersComponent } from './user-filters.component';
import { overrideRootServices } from '@tetra/testing/override-providers';

describe('UserFiltersComponent', () => {
  beforeEach(async () => {
    await overrideRootServices(
      TestBed.configureTestingModule({
        imports: [
          UserFiltersComponent,
          RouterTestingModule,
        ],
      })
    ).compileComponents();
  });

  it('should create', () => {
    const fixture = TestBed.createComponent(UserFiltersComponent);
    fixture.detectChanges();
    expect(fixture.componentInstance).toBeTruthy();
  });
});
