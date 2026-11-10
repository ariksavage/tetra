import { Component, ChangeDetectionStrategy } from '@angular/core';
import { FieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
    selector: '.field.email',
    imports: [CommonModule, FormsModule],
    templateUrl: '../field/field.component.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './field-email.component.scss'
})

export class FieldEmailComponent extends FieldComponent {
  override type = 'email';

  override modelValid() {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(this.model);
  }
}
