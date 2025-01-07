import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FieldWYSIWYGComponent } from './field-wysiwyg.component';

describe('FieldWYSIWYGComponent', () => {
  let component: FieldWYSIWYGComponent;
  let fixture: ComponentFixture<FieldWYSIWYGComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FieldWYSIWYGComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FieldWYSIWYGComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
