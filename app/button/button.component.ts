import { Component, Input, HostBinding, ViewChild, ElementRef } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
		standalone: true,
    selector: 'button, .btn',
    imports: [CommonModule],
    templateUrl: './button.component.html',
    styleUrl: './button.component.scss'
})
export class TetraButtonComponent {
  @Input() iBefore: string = '';
  @Input() iAfter: string = '';
  @Input() text: string = '';
  @HostBinding('attr.aria-label')
  @HostBinding('attr.title')
  @Input() title: string = '';
}
