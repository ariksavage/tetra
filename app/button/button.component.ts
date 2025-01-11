import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'button',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './button.component.html',
  styleUrl: './button.component.scss'
})
export class TetraButtonComponent {
  @Input() icon: string = '';
  @Input() iconStyle: string = 'solid';
  @Input() text: string = '';

  iconClass() {
    return 'fa-' + this.icon + ' fa-' + this.iconStyle;
  }
}
