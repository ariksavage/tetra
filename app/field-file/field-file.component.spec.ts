import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TetraFieldFileComponent } from './field-file.component';

describe('TetraFieldFileComponent', () => {
  let component: TetraFieldFileComponent;
  let fixture: ComponentFixture<TetraFieldFileComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TetraFieldFileComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TetraFieldFileComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
