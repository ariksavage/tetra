import { Component, Input, HostBinding } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
	standalone: true,
  selector: '.list-item',
  imports: [CommonModule],
  templateUrl: './list-item.component.html',
  styleUrl: './list-item.component.scss'
})
export class ListItemComponent {
  @Input() title: string = '';
  @Input() icon: string = '';
  @Input() location: string = '';
  @HostBinding('class.clickable')
  get isClickable(): boolean {
    return !!this.location;
  }

  click() {
    if (this.location){
      window.location.href = this.location;
    }
  }
}
