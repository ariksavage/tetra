import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TetraFilteredListComponent } from './filtered-list.component';

describe('TetraFilteredListComponent', () => {
  let component: TetraFilteredListComponent;
  let fixture: ComponentFixture<TetraFilteredListComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TetraFilteredListComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TetraFilteredListComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
