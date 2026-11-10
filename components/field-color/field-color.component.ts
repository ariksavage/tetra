import { Component, Input, Output, EventEmitter } from '@angular/core';
import { FieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
	standalone: true,
  selector: '.field.color',
  imports: [ CommonModule, FormsModule ],
  templateUrl: './field-color.component.html',
  styleUrl: './field-color.component.scss'
})
export class FieldColorComponent extends FieldComponent {
  override type = 'color';
}
