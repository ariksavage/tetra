import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FieldDateComponent } from './field-date.component';

describe('FieldDateComponent', () => {
  let component: FieldDateComponent;
  let fixture: ComponentFixture<FieldDateComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FieldDateComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FieldDateComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
