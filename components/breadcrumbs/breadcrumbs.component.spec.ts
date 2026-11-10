import { TestBed } from '@angular/core/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { BreadcrumbsComponent } from './breadcrumbs.component';
import { overrideRootServices } from '@tetra/testing/override-providers';

describe('BreadcrumbsComponent', () => {
  beforeEach(async () => {
    await overrideRootServices(
      TestBed.configureTestingModule({
        imports: [
          BreadcrumbsComponent,
          RouterTestingModule,
        ],
      })
    ).compileComponents();
  });

  it('should create', () => {
    const fixture = TestBed.createComponent(BreadcrumbsComponent);
    fixture.detectChanges();
    expect(fixture.componentInstance).toBeTruthy();
  });
});
