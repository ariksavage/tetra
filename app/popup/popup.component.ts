import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
		standalone: true,
    selector: '.popup',
    imports: [CommonModule],
    templateUrl: './popup.component.html',
    styleUrl: './popup.component.scss'
})
export class TetraPopupComponent {
  @Input() title: string = '';
  @Input() open: boolean = false;
  @Output() onClose: EventEmitter<any> = new EventEmitter<any>();
  toggle() {
    this.open = !this.open;

    if (!this.open) {
      this.onClose.emit();
    }
  }
}
