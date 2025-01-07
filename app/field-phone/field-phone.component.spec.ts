import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FieldPhoneComponent } from './field-phone.component';

describe('FieldPhoneComponent', () => {
  let component: FieldPhoneComponent;
  let fixture: ComponentFixture<FieldPhoneComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FieldPhoneComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FieldPhoneComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
