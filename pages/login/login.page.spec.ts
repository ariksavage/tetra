import { TestBed } from '@angular/core/testing';
import { LoginPageComponent } from './login.page';
import { createPageTestBed } from '@tetra/testing/create-page-testbed';

describe('LoginPageComponent', () => {
  beforeEach(async () => {
    await createPageTestBed(LoginPageComponent);
  });

  it('should create', () => {
    const fixture = TestBed.createComponent(LoginPageComponent);
    fixture.detectChanges();
    expect(fixture.componentInstance).toBeTruthy();
  });
});
