import { TestBed } from '@angular/core/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { AppMenuComponent } from './app-menu.component';
import { overrideRootServices, rootServiceProviders } from '@tetra/testing/override-providers';

describe('AppMenuComponent', () => {
  beforeEach(async () => {
    await overrideRootServices(
      TestBed.configureTestingModule({
        imports: [
          AppMenuComponent,
          RouterTestingModule,
        ],
        providers: [...rootServiceProviders],
      })
    ).compileComponents();
  });

  it('should create', () => {
    const fixture = TestBed.createComponent(AppMenuComponent);
    fixture.detectChanges();
    expect(fixture.componentInstance).toBeTruthy();
  });
});
