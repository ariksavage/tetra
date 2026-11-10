import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FieldRadiosComponent } from './field-radios.component';

describe('FieldRadiosComponent', () => {
  let component: FieldRadiosComponent;
  let fixture: ComponentFixture<FieldRadiosComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FieldRadiosComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FieldRadiosComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
