import { Component, Input, ChangeDetectionStrategy } from '@angular/core';
import { FieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
    selector: '.field.number',
    imports: [CommonModule, FormsModule],
    templateUrl: './field-number.component.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './field-number.component.scss'
})
export class FieldNumberComponent extends FieldComponent {
  override type = 'number';
  @Input() min: number|null = null;
  @Input() max: number|null = null;
  @Input() step: number|null = null;
  @Input() after: string = '';
  @Input() hideZero: boolean = false;

  override modelExists() {
    return this.model == 0 || !!this.model;
  }

  override beforeModelChange() {
    if (this.hideZero && this.model == 0){
      this.model = null;
    }
    return this.model;
  }
}
