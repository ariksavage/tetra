import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
		standalone: true,
    selector: 'section.collapse',
    imports: [CommonModule],
    templateUrl: './section-collapse.component.html',
    styleUrl: './section-collapse.component.scss'
})
export class TetraSectionCollapseComponent {
  @Input() open: boolean = false;
  @Output() openChange: EventEmitter<boolean> = new EventEmitter<boolean>();
  @Input() title: string = '';

  toggle() {
    this.open = !this.open;
  }
}
