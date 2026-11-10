import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FieldTextAreaComponent } from './field-textarea.component';

describe('FieldTextAreaComponent', () => {
  let component: FieldTextAreaComponent;
  let fixture: ComponentFixture<FieldTextAreaComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FieldTextAreaComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FieldTextAreaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
