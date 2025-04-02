import { Component, Input, Output, EventEmitter } from '@angular/core';
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
export class TetraFieldMultiSelectComponent extends Component {
  @Input() label: string = '';
  @Input() model: any = null;
  @Output() modelChange: EventEmitter<any> = new EventEmitter<any>();
  @Input() items: Array<any> = [];
  @Input() titleFunc: any = null;
  @Input() newFunc: any = null;
  filter: string = '';

  filteredItems() {
    return this.items.filter(x => {
      return !this.model.includes(x) && (!this.filter || this.titleFunc(x).indexOf(this.filter) > -1);
    });
  }

  matchItem() {
    if (!this.filter){
      return true;
    }
    let matches = this.items.filter(item => this.titleFunc(item) == this.filter);
    return matches.length > 0;
  }

  add(item: any){
    this.model.push(item);
  }
  remove(i: number) {
    this.model.splice(i, 1);
  }

  new() {
    this.newFunc(this.filter);
    this.filter = '';
  }
}
