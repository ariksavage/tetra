import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TetraFieldColorComponent } from './field-color.component';

describe('TetraFieldColorComponent', () => {
  let component: TetraFieldColorComponent;
  let fixture: ComponentFixture<TetraFieldColorComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TetraFieldColorComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TetraFieldColorComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
