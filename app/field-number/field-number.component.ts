import { Component } from '@angular/core';
import { TetraFieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
  selector: '.field.number',
  standalone: true,
  imports: [ CommonModule, FormsModule ],
  templateUrl: '../field/field.component.html',
  styleUrl: '../field/field.component.scss'
})
export class TetraFieldNumberComponent extends TetraFieldComponent {
  override type = 'number';
}
