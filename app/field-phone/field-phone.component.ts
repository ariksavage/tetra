import { Component } from '@angular/core';
import { FieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
		standalone: true,
  selector: '.field.phone',
  standalone: true,
  imports: [ CommonModule, FormsModule ],
  templateUrl: '../field/field.component.html',
  styleUrl: './field-phone.component.scss'
})
export class FieldPhoneComponent extends FieldComponent {
  override type = 'tel';
}
