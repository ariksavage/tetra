import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TetraFieldCheckboxComponent } from './field-checkbox.component';

describe('TetraFieldCheckboxComponent', () => {
  let component: TetraFieldCheckboxComponent;
  let fixture: ComponentFixture<TetraFieldCheckboxComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TetraFieldCheckboxComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TetraFieldCheckboxComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
