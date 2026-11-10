import { TestBed } from '@angular/core/testing';
import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { EditMenuItemComponent } from './edit-menu-item.component';
import { FieldTextComponent } from '@tetra/components/field-text/field-text.component';
import { PopupComponent } from '@tetra/components/popup/popup.component';
import { overrideRootServices, rootServiceProviders } from '@tetra/testing/override-providers';

@Component({ selector: '[mock-field-text]', standalone: true, template: '' })
class MockFieldTextComponent {}

@Component({ selector: '[mock-popup]', standalone: true, template: '' })
class MockPopupComponent {}

const mockItem = {
  id: 1,
  title: 'Test Item',
  path: '/test',
  icon: 'fa-home',
  weight: 1,
  parent: 0,
  children: []
};

describe('EditMenuItemComponent', () => {
  beforeEach(async () => {
    await overrideRootServices(
      TestBed.configureTestingModule({
        imports: [EditMenuItemComponent, CommonModule],
        providers: [...rootServiceProviders]
      })
    )
    .overrideComponent(EditMenuItemComponent, {
      remove: { imports: [FieldTextComponent, PopupComponent] },
      add: { imports: [MockFieldTextComponent, MockPopupComponent] },
    })
    .compileComponents();
  });

  it('should create', () => {
    const fixture = TestBed.createComponent(EditMenuItemComponent);
    fixture.componentInstance.item = { ...mockItem };
    fixture.detectChanges();
    expect(fixture.componentInstance).toBeTruthy();
  });

  it('should add a child item', () => {
    const fixture = TestBed.createComponent(EditMenuItemComponent);
    fixture.componentInstance.item = { ...mockItem, children: [] };
    fixture.detectChanges();
    fixture.componentInstance.addChild();
    expect(fixture.componentInstance.item.children).toHaveLength(1);
    expect(fixture.componentInstance.item.children[0].title).toBe('New Item');
  });

  it('should remove a child item', () => {
    const child = { id: 2, title: 'Child', weight: 1 };
    const fixture = TestBed.createComponent(EditMenuItemComponent);
    fixture.componentInstance.item = { ...mockItem, children: [child] };
    fixture.detectChanges();
    fixture.componentInstance.removeChild(child);
    expect(fixture.componentInstance.item.children).toHaveLength(0);
  });

  it('should emit remove event on removeItem', () => {
    const fixture = TestBed.createComponent(EditMenuItemComponent);
    fixture.componentInstance.item = { ...mockItem };
    fixture.detectChanges();
    jest.spyOn(fixture.componentInstance.remove, 'emit');
    fixture.componentInstance.removeItem();
    expect(fixture.componentInstance.remove.emit).toHaveBeenCalledWith(mockItem);
  });
});
