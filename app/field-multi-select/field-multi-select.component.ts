import { Component, Input, Output, EventEmitter, ViewChild, ElementRef } from '@angular/core';
// import { TetraFieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
		standalone: true,
    selector: '.field.multi-select',
    imports: [CommonModule, FormsModule],
    templateUrl: './field-multi-select.component.html',
    styleUrl: './field-multi-select.component.scss'
})
export class TetraFieldMultiSelectComponent {
  @Input() label: string = '';
  @Input() placeholder: string = '';
  @Input() model: any = null;
  @Output() modelChange: EventEmitter<any> = new EventEmitter<any>();
  @Output() afterChange: EventEmitter<any> = new EventEmitter<any>();
  @Input() items: Array<any> = [];
  @Input() titleFunc: any = null;
  @Input() newFunc: any = null;
  filter: string = '';
  focus: boolean = false;
  blurTO: any = null;

  id(){
    return 'multi-select-' + this.label.toLowerCase();
  }

  filteredItems() {
    return this.items.filter((item: any) => {
      let include = true;
      if (this.filter && this.titleFunc(item).indexOf(this.filter) == -1) {
        include = false;
      } else {
        this.model.forEach((modelItem: any) => {
          if (this.titleFunc(modelItem) == this.titleFunc(item)){
            include = false;
          }
        })
      }
      return include;
      // return !this.model.includes(x) && (!this.filter || this.titleFunc(x).indexOf(this.filter) > -1);
    }).sort((a: any, b: any) => {
      return this.titleFunc(a).localeCompare(this.titleFunc(b));
    });
  }

  matchItem() {
    if (!this.filter){
      return true;
    }
    let matches = this.items.filter(item => this.titleFunc(item) == this.filter);
    return matches.length > 0;
  }

  onFocus() {

    clearTimeout(this.blurTO);
    this.focus = true;
  }

  onBlur() {
    this.blurTO = setTimeout(() => {
      this.focus = false;
    }, 10);
  }

  add(item: any){
    this.model.push(item);
    this.afterChange.emit(this.model);
  }
  remove(i: number) {
    this.model.splice(i, 1);
    this.afterChange.emit(this.model);
  }

  new() {
    this.newFunc(this.filter);
    this.filter = '';
  }
}
