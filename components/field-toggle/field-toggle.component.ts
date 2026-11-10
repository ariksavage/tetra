import { Component, Input, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FieldComponent } from '@tetra/components/field/field.component';

@Component({
  standalone: true,
  selector: '.field.toggle',
  imports: [CommonModule],
  templateUrl: './field-toggle.component.html',
  styleUrl: './field-toggle.component.scss'
})
export class FieldToggleComponent extends FieldComponent {
  toggle() {
    this.model = !this.model;
    this.update();
  }

  aria() {
    return 'Toggle "' + this.label + '"';
  }
}
