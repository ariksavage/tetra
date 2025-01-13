import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TetraBreadcrumbsComponent } from './breadcrumbs.component';

describe('TetraBreadcrumbsComponent', () => {
  let component: TetraBreadcrumbsComponent;
  let fixture: ComponentFixture<TetraBreadcrumbsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TetraBreadcrumbsComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TetraBreadcrumbsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
