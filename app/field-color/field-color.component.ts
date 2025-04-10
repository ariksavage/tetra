import { Component, Input, Output, EventEmitter } from '@angular/core';
import { TetraFieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
	standalone: true,
  selector: '.field.color',
  imports: [ CommonModule, FormsModule ],
  templateUrl: './field-color.component.html',
  styleUrl: './field-color.component.scss'
})
export class TetraFieldColorComponent extends TetraFieldComponent {
  override type = 'color';
}
