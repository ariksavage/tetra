import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FieldMultiSelectComponent } from './field-multi-select.component';

describe('FieldMultiSelectComponent', () => {
  let component: FieldMultiSelectComponent;
  let fixture: ComponentFixture<FieldMultiSelectComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FieldMultiSelectComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FieldMultiSelectComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
