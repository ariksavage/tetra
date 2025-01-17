import { Component, Input } from '@angular/core';
import { TetraFieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
  selector: '.field.number',
  standalone: true,
  imports: [ CommonModule, FormsModule ],
  templateUrl: './field-number.component.html',
  styleUrl: './field-number.component.scss'
})
export class TetraFieldNumberComponent extends TetraFieldComponent {
  override type = 'number';
  @Input() min: number|null = null;
  @Input() max: number|null = null;
  @Input() step: number|null = null;
}
