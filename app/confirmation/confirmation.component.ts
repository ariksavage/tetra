import { Component, Input, Output, EventEmitter } from '@angular/core';
import { TetraButtonComponent } from '@tetra/button/button.component';
import { CommonModule } from '@angular/common';

@Component({
  selector: '.confirmation',
  standalone: true,
  imports: [CommonModule, TetraButtonComponent],
  templateUrl: './confirmation.component.html',
  styleUrl: './confirmation.component.scss'
})
export class TetraConfirmationComponent {
  @Input() title: string = '';
  @Input() cancelText: string = 'Cancel';
  @Input() confirmText: string = 'Confirm';
  @Output() cancel: EventEmitter<any> = new EventEmitter<any>;
  @Output() confirm: EventEmitter<any> = new EventEmitter<any>;
  cancelAction(){
    console.log('cancel');
    this.cancel.emit(true);
  }
  confirmAction(){
    console.log('confirm');
    this.confirm.emit(true);
  }
}
