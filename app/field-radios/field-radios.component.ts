import { Component, Input } from '@angular/core';
import { TetraFieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
		standalone: true,
    selector: '.field.radios',
    imports: [CommonModule, FormsModule],
    templateUrl: './field-radios.component.html',
    styleUrl: './field-radios.component.scss'
})
export class TetraFieldRadiosComponent extends TetraFieldComponent {
  @Input() options: any = null;
  @Input() labelFunc: any = null;
  @Input() valueFunc: any = null;

  set(item: any) {
    this.model = this.itemValue(item);
    this.update();
  }

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
