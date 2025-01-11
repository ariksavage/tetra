import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TetraFieldEmailComponent } from './field-email.component';

describe('TetraFieldEmailComponent', () => {
  let component: TetraFieldEmailComponent;
  let fixture: ComponentFixture<TetraFieldEmailComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TetraFieldEmailComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TetraFieldEmailComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
