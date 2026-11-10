import { Component, Input, Output, EventEmitter, ChangeDetectionStrategy } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { FieldComponent } from '../field/field.component';

@Component({
    selector: '.field.password',
    imports: [CommonModule, FormsModule],
    templateUrl: './field-password.component.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './field-password.component.scss'
})
export class FieldPasswordComponent extends FieldComponent {
  show: boolean = false;

  toggleShow() {
    this.show = !this.show;
  }
}
