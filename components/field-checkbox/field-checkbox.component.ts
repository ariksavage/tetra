import { Component, Input, ChangeDetectionStrategy } from '@angular/core';
import { FieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
	standalone: true,
  selector: '.field.checkbox',
  imports: [ CommonModule, FormsModule ],
  templateUrl: './field-checkbox.component.html',
  changeDetection: ChangeDetectionStrategy.Eager,
  styleUrl: './field-checkbox.component.scss'
})

export class FieldCheckboxComponent extends FieldComponent {
  @Input() valueTrue: string = 'true';
  @Input() valueFalse: string = 'false';
  @Input() description: string = '';
  override type = 'checkbox';

  toggle(){
    this.model = !this.model;
    this.update();
  }
  override onInit() {
    this.model = !!this.model;
    this.beforeModelChange();
  }
}
