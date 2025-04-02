import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TetraFieldTextComponent } from './field-text.component';

describe('TetraFieldTextComponent', () => {
  let component: TetraFieldTextComponent;
  let fixture: ComponentFixture<TetraFieldTextComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TetraFieldTextComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TetraFieldTextComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
