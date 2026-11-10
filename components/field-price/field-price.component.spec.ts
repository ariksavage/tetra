import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FieldPriceComponent } from './field-price.component';

describe('FieldPriceComponent', () => {
  let component: FieldPriceComponent;
  let fixture: ComponentFixture<FieldPriceComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FieldPriceComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FieldPriceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
