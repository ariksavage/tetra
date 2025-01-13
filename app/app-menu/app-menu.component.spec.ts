import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TetraAppMenuComponent } from './app-menu.component';

describe('TetraAppMenuComponent', () => {
  let component: TetraAppMenuComponent;
  let fixture: ComponentFixture<TetraAppMenuComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TetraAppMenuComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TetraAppMenuComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
