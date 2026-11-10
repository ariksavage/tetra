import { Component, ChangeDetectionStrategy } from '@angular/core';
import { FieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
    selector: '.field.text',
    imports: [CommonModule, FormsModule],
    templateUrl: '../field/field.component.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './field-text.component.scss'
})
export class FieldTextComponent extends FieldComponent {
  override type = 'text';
}
