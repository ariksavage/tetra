import { Component } from '@angular/core';
import { FieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
  selector: '.field.email',
  standalone: true,
  imports: [ CommonModule, FormsModule ],
  templateUrl: '../field/field.component.html',
  styleUrl: '../field/field.component.scss'
})
export class FieldEmailComponent extends FieldComponent {
  override type = 'email';
}
