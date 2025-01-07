import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TetraPage } from './page.component';

describe('TetraPage', () => {
  let component: TetraPage;
  let fixture: ComponentFixture<TetraPage>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TetraPage]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TetraPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
