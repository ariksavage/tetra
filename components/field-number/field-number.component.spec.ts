import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FieldNumberComponent } from './field-number.component';

describe('FieldNumberComponent', () => {
  let component: FieldNumberComponent;
  let fixture: ComponentFixture<FieldNumberComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FieldNumberComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FieldNumberComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
