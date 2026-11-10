import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SectionCollapseComponent } from './section-collapse.component';

describe('SectionCollapseComponent', () => {
  let component: SectionCollapseComponent;
  let fixture: ComponentFixture<SectionCollapseComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [SectionCollapseComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SectionCollapseComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
