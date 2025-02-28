import { Component } from '@angular/core';
import { TetraFieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
		standalone: true,
  selector: '.field.email',
  standalone: true,
  imports: [ CommonModule, FormsModule ],
  templateUrl: '../field/field.component.html',
  styleUrl: './field-email.component.scss'
})

export class TetraFieldEmailComponent extends TetraFieldComponent {
  override type = 'email';
}
