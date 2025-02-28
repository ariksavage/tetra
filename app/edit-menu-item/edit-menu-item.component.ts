import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TetraFieldTextComponent } from '@tetra/field-text/field-text.component';
import { TetraPopupComponent } from '@tetra/popup/popup.component';

@Component({
	standalone: true,
  selector: '.edit-menu-item',
  imports: [CommonModule, TetraFieldTextComponent, TetraPopupComponent],
  templateUrl: './edit-menu-item.component.html',
  styleUrl: './edit-menu-item.component.scss'
})
export class TetraEditMenuItemComponent {
  @Input() item: any = null;
  @Output() remove: EventEmitter<any> = new EventEmitter<any>();
  @Output() reorder: EventEmitter<any> = new EventEmitter<any>();
  editIcon: boolean = false;
  @Input() root: boolean = false;
  @Input() maxWeight: number = 1;

  ngOnInit() {
    this.reorder.emit();
  }

  addChild() {
    this.item.children.push({
      title: 'New Item',
      path: this.item.path + '/',
      parent: this.root? 0 : this.item.id,
      icon: 'fa-question',
      weight: this.item.children.length + 1,
      children: []
    })
  }
  removeItem() {
    this.remove.emit(this.item);
  }
  removeChild(item: any) {
    const i = this.item.children.indexOf(item);
    this.item.children.splice(i, 1);
  }

  onReorder(){
    this.reorder.emit();
  }

  move(item: any, dir: number) {
    // set weight just above the next item
    let weight = item.weight + (dir * 1.5);
    item.weight = weight;
    this.reorder.emit();
  }

  orderWeight() {
    // sort by weight
    this.item.children.sort(function(a: any, b: any) {
      return a.weight - b.weight;
    });
    // Normalize weights to whole numbers
    let i = 0;
    this.item.children = this.item.children.map((item: any) => {
      item.weight = i;
      i++;
      return item;
    });
  }
}
