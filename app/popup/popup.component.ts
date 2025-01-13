import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: '.popup',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './popup.component.html',
  styleUrl: './popup.component.scss'
})
export class TetraPopupComponent {
  @Input() title: string = '';
  open: boolean = false;
  toggle() {
    this.open = !this.open;
  }
}
