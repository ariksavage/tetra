import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TetraFieldDateComponent } from './field-date.component';

describe('TetraFieldDateComponent', () => {
  let component: TetraFieldDateComponent;
  let fixture: ComponentFixture<TetraFieldDateComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TetraFieldDateComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TetraFieldDateComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
