import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RouteNavComponent } from './route-nav.component';

describe('RouteNavComponent', () => {
  let component: RouteNavComponent;
  let fixture: ComponentFixture<RouteNavComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [RouteNavComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RouteNavComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
