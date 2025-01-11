import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TetraFieldPasswordComponent } from './field-password.component';

describe('TetraFieldPasswordComponent', () => {
  let component: TetraFieldPasswordComponent;
  let fixture: ComponentFixture<TetraFieldPasswordComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TetraFieldPasswordComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TetraFieldPasswordComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
