import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TetraFieldComponent } from './field.component';

describe('TetraFieldComponent', () => {
  let component: TetraFieldComponent;
  let fixture: ComponentFixture<TetraFieldComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TetraFieldComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TetraFieldComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
