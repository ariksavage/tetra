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
}
