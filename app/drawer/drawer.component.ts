import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'div.drawer',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './drawer.component.html',
  styleUrl: './drawer.component.scss'
})
export class TetraDrawerComponent {
  @Input() title: string = '';
  @Input() icon: string = '';
  @Input() position: string = 'bottom';
  open: boolean = false;
  transition: boolean = false;

  toggle() {
    const self = this;
    if (this.open) {
      this.open = false;
      setTimeout(function() {
        self.transition = false;
      }, 500);
    } else {
      this.transition = true;
      setTimeout(function() {
        self.open = true;
      }, 100);
    }
  }

  contentClass() {
    let cl = '';
    cl += ' ' + this.position;
    if (this.transition) {
      cl += ' transition';
    }
    if (this.open) {
      cl += ' open';
    }
    return cl.trim();
  }

  closeBtnIcon() {
    let cl = '';
    switch(this.position) {
      case 'top':
        cl = 'fa-angle-up';
        break;
      case 'right':
        cl = 'fa-angle-right';
        break;
      case 'bottom':
        cl = 'fa-angle-down';
        break;
      case 'left':
        cl = 'fa-angle-left';
        break;
    }
    return cl;
  }
}
