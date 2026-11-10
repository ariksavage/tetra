import { TestBed } from '@angular/core/testing';
import { Component } from '@angular/core';
import { RouterTestingModule } from '@angular/router/testing';
import { PasswordResetPage } from './password-reset.page';
import { FieldPasswordComponent } from '@tetra/components/field-password/field-password.component';
import { overrideRootServices, rootServiceProviders } from '@tetra/testing/override-providers';

@Component({ selector: '[mock-field-password]', standalone: true, template: '' })
class MockFieldPasswordComponent {}

describe('PasswordResetPage', () => {
  beforeEach(async () => {
    await overrideRootServices(
      TestBed.configureTestingModule({
        imports: [
          PasswordResetPage,
          RouterTestingModule,
        ],
        providers: [...rootServiceProviders],
      })
    )
    .overrideComponent(PasswordResetPage, {
      remove: { imports: [FieldPasswordComponent] },
      add: { imports: [MockFieldPasswordComponent] },
    })
    .compileComponents();

    jest.clearAllMocks();
  });

  it('should create', () => {
    const fixture = TestBed.createComponent(PasswordResetPage);
    fixture.detectChanges();
    expect(fixture.componentInstance).toBeTruthy();
  });

  it('should return false from canSavePassword when passwords are empty', () => {
    const fixture = TestBed.createComponent(PasswordResetPage);
    expect(fixture.componentInstance.canSavePassword()).toBeFalsy();
  });

  it('should return false from canSavePassword when passwords do not match', () => {
    const fixture = TestBed.createComponent(PasswordResetPage);
    fixture.componentInstance.password = 'abc';
    fixture.componentInstance.password2 = 'xyz';
    expect(fixture.componentInstance.canSavePassword()).toBeFalsy();
  });

  it('should return true from canSavePassword when passwords match', () => {
    const fixture = TestBed.createComponent(PasswordResetPage);
    fixture.componentInstance.password = 'abc123';
    fixture.componentInstance.password2 = 'abc123';
    expect(fixture.componentInstance.canSavePassword()).toBeTruthy();
  });
});
