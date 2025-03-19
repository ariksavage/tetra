import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'nav.tabs',
  standalone: true,
  imports: [ CommonModule ],
  templateUrl: './tabs.component.html',
  styleUrl: './tabs.component.scss'
})
export class TetraTabsComponent {
  @Input() current: string = '';
  @Output() currentChange: EventEmitter<string> = new EventEmitter<string>();
  @Input() sections: Array<string> = [];

  set(section: string) {
    this.current = section;
    this.currentChange.emit(this.current);
  }
}
