import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
		standalone: true,
    selector: 'section.collapse',
    imports: [CommonModule],
    templateUrl: './section-collapse.component.html',
    styleUrl: './section-collapse.component.scss'
})
export class SectionCollapseComponent {
  @Input() open: boolean = false;
  @Input() title: string = '';

  toggle() {
    this.open = !this.open;
  }
}
