import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: '.confirmation',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './confirmation.component.html',
  styleUrl: './confirmation.component.scss'
})
export class TetraConfirmationComponent {
  @Input() title: string = '';
  @Input() cancelText: string = 'Cancel';
  @Input() confirmText: string = 'Confirm';
  @Output() cancel: EventEmitter<any> = new EventEmitter<any>;
  @Output() confirm: EventEmitter<any> = new EventEmitter<any>;
  cancelAction() {
    this.cancel.emit(true);
  }
  confirmAction() {
    this.confirm.emit(true);
  }
}
