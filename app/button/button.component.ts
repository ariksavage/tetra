import { Component, Input, HostBinding } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
		standalone: true,
    selector: 'button',
    imports: [CommonModule],
    templateUrl: './button.component.html',
    styleUrl: './button.component.scss'
})
export class TetraButtonComponent {
  @Input() icon: string = '';
  @Input() iconStyle: string = 'solid';
  @Input() text: string = '';
  // @Input() title: string = '';
  // @HostBinding('attr.aria-label') getLabel() {return this.title;}
  @HostBinding('attr.aria-label')
  @Input() title: string = '';

  iconClass() {
    return 'fa-' + this.icon + ' fa-' + this.iconStyle;
  }
}
