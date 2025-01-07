import { Component, Input, Output, EventEmitter } from '@angular/core';
import { FieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { ColorPickerModule } from 'ngx-color-picker'; // https://www.npmjs.com/package/ngx-color-picker

@Component({
  selector: '.field.color',
  standalone: true,
  imports: [ CommonModule, FormsModule, ColorPickerModule ],
  templateUrl: './field-color.component.html',
  styleUrl: './field-color.component.scss'
})
export class FieldColorComponent extends FieldComponent {
  override type = 'color';
}
