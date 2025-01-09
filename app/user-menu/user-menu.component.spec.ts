import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TetraUserMenuComponent } from './user-menu.component';

describe('TetraUserMenuComponent', () => {
  let component: TetraUserMenuComponent;
  let fixture: ComponentFixture<TetraUserMenuComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TetraUserMenuComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TetraUserMenuComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
