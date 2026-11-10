import { TestBed } from '@angular/core/testing';
import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { LoginForm } from './login-form.component';
import { FieldTextComponent } from '@tetra/components/field-text/field-text.component';
import { FieldPasswordComponent } from '@tetra/components/field-password/field-password.component';
import { UserService } from '@tetra/services/user.service';
import { mockUserService } from '@tetra/services/user.service.mock';
import { overrideRootServices, rootServiceProviders } from '@tetra/testing/override-providers';

@Component({ selector: '[field-text]', standalone: true, template: '' })
class MockFieldTextComponent {}

@Component({ selector: '[field-password]', standalone: true, template: '' })
class MockFieldPasswordComponent {}

describe('LoginForm', () => {
  beforeEach(async () => {
    await overrideRootServices(
      TestBed.configureTestingModule({
        imports: [LoginForm, CommonModule, FormsModule],
        providers: [...rootServiceProviders],
      })
    )
    .overrideProvider(UserService, { useValue: mockUserService })
    .overrideComponent(LoginForm, {
      remove: { imports: [FieldTextComponent, FieldPasswordComponent] },
      add: { imports: [MockFieldTextComponent, MockFieldPasswordComponent] },
    })
    .compileComponents();

    jest.clearAllMocks();
  });

  it('should create', () => {
    const fixture = TestBed.createComponent(LoginForm);
    fixture.detectChanges();
    expect(fixture.componentInstance).toBeTruthy();
  });

  it('should call userService.login with credentials', async () => {
    mockUserService.login.mockResolvedValue({ id: 1, username: 'test' });
    const fixture = TestBed.createComponent(LoginForm);
    fixture.componentInstance.username = 'test';
    fixture.componentInstance.password = 'password';
    await fixture.componentInstance.login();
    expect(mockUserService.login).toHaveBeenCalledWith('test', 'password');
  });

  it('should not call login if username or password is empty', async () => {
    const fixture = TestBed.createComponent(LoginForm);
    fixture.componentInstance.username = '';
    fixture.componentInstance.password = '';
    await fixture.componentInstance.login();
    expect(mockUserService.login).not.toHaveBeenCalled();
  });

  it('should call forgotPassword and set resetSent', async () => {
    mockUserService.forgotPassword.mockResolvedValue({ message: 'Email sent' });
    const fixture = TestBed.createComponent(LoginForm);
    fixture.componentInstance.username = 'test';
    await fixture.componentInstance.forgotPassword();
    expect(mockUserService.forgotPassword).toHaveBeenCalledWith('test');
    expect(fixture.componentInstance.resetSent).toBe(true);
    expect(fixture.componentInstance.message).toBe('Email sent');
  });
});
