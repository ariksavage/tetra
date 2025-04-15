import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TetraFieldRadiosComponent } from './field-radios.component';

describe('TetraFieldRadiosComponent', () => {
  let component: TetraFieldRadiosComponent;
  let fixture: ComponentFixture<TetraFieldRadiosComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TetraFieldRadiosComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TetraFieldRadiosComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
