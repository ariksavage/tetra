import { Component, Input, Output, EventEmitter } from '@angular/core';
import { TetraFieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { ColorPickerModule } from 'ngx-color-picker'; // https://www.npmjs.com/package/ngx-color-picker

@Component({
		standalone: true,
  selector: '.field.color',
  standalone: true,
  imports: [ CommonModule, FormsModule, ColorPickerModule ],
  templateUrl: './field-color.component.html',
  styleUrl: './field-color.component.scss'
})
export class TetraFieldColorComponent extends TetraFieldComponent {
  override type = 'color';
}
