import { Component, Input } from '@angular/core';
import { TetraFieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
		standalone: true,
    selector: '.field.select',
    imports: [CommonModule, FormsModule],
    templateUrl: './field-select.component.html',
    styleUrl: './field-select.component.scss'
})
export class TetraFieldSelectComponent extends TetraFieldComponent {
  @Input() options: any = null;
  @Input() labelFunc: any = null;
  @Input() valueFunc: any = null;

  itemLabel(item: any) {
    if (this.labelFunc) {
      return this.labelFunc(item);
    } else {
      return item;
    }
  }

  itemValue(item: any) {
    if (this.valueFunc) {
      return this.valueFunc(item);
    } else {
      return item;
    }
  }
}
