import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TetraPopupComponent } from './popup.component';

describe('TetraPopupComponent', () => {
  let component: TetraPopupComponent;
  let fixture: ComponentFixture<TetraPopupComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TetraPopupComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TetraPopupComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
