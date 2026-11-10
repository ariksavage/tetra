import { Component, Input } from '@angular/core';
import { FieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
		standalone: true,
    selector: '.field.price',
    imports: [CommonModule, FormsModule],
    templateUrl: './field-price.component.html',
    styleUrl: './field-price.component.scss'
})
export class FieldPriceComponent extends FieldComponent {
  override type = 'number';
  @Input() min: number|null = null;
  @Input() max: number|null = null;
  @Input() step: number|null = 0.05;

  override onChange(e: any) {
    this.model = parseFloat(this.model).toFixed(2);
    this.update();
  }
}
