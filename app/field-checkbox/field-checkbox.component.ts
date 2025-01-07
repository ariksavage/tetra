import { Component, Input } from '@angular/core';
import { FieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
  selector: '.field.checkbox',
  standalone: true,
  imports: [ CommonModule, FormsModule ],
  templateUrl: './field-checkbox.component.html',
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
}
