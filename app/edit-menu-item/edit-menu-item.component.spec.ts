import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TetraEditMenuItemComponent } from './edit-menu-item.component';

describe('TetraEditMenuItemComponent', () => {
  let component: TetraEditMenuItemComponent;
  let fixture: ComponentFixture<TetraEditMenuItemComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TetraEditMenuItemComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TetraEditMenuItemComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
