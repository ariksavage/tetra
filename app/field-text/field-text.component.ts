import { Component } from '@angular/core';
import { TetraFieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
		standalone: true,
    selector: '.field.text',
    imports: [CommonModule, FormsModule],
    templateUrl: '../field/field.component.html',
    styleUrl: './field-text.component.scss'
})
export class TetraFieldTextComponent extends TetraFieldComponent {
  override type = 'text';
}
