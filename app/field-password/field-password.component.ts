import { Component, Input, Output, EventEmitter } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { TetraFieldComponent } from '../field/field.component';

@Component({
		standalone: true,
    selector: '.field.password',
    imports: [CommonModule, FormsModule],
    templateUrl: './field-password.component.html',
    styleUrl: './field-password.component.scss'
})
export class TetraFieldPasswordComponent extends TetraFieldComponent {
  show: boolean = false;

  toggleShow() {
    this.show = !this.show;
  }
}
