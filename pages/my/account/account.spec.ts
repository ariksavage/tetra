import { TestBed } from '@angular/core/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { MyAccountPageComponent } from './account.page';
import { overrideRootServices, rootServiceProviders } from '@tetra/testing/override-providers';

describe('MyAccountPageComponent', () => {
  beforeEach(async () => {
    await overrideRootServices(
      TestBed.configureTestingModule({
        imports: [
          MyAccountPageComponent,
          RouterTestingModule,
        ],
        providers: [...rootServiceProviders],
      })
    ).compileComponents();

    jest.clearAllMocks();
  });

  it('should create', () => {
    const fixture = TestBed.createComponent(MyAccountPageComponent);
    fixture.detectChanges();
    expect(fixture.componentInstance).toBeTruthy();
  });
});
