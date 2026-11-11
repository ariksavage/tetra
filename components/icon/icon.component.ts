import { Component, Input, Renderer2, ElementRef } from '@angular/core';

@Component({
  selector: '.icon',
  standalone: true,
  imports: [],
  templateUrl: './icon.component.html',
  styleUrl: './icon.component.scss'
})
export class IconComponent {
  @Input() icon: string = '';
  @Input() style: string = 'solid';

  constructor(private ref: ElementRef) {}

  ngOnInit() {
    this.ref.nativeElement.className += this.iconClass();
    this.ref.nativeElement.setAttribute('aria-hidden', 'true');
  }

  iconClass() {
    let cl = '';
    switch(this.icon) {
      case 'archive':
        cl = 'fa-box-archive';
        break;
      case 'copy':
        cl = 'fa-copy';
        break;
      case 'date':
        cl = 'fa-calendar-days';
        break;
      case 'delete':
        cl = 'fa-xmark';
        break;
      case 'edit':
        cl = 'fa-pencil';
        break;
      case 'published':
        cl = 'fa-circle-check';
        break;
      case 'republish':
        cl = 'fa-bolt-lightning';
        break;
      case 'tag':
        cl = 'fa-tag';
        break;
      default:
        console.error('Icon ' + this.icon + ' is not defined');
        break;
    }
    return ' fa-' + this.style + ' ' + cl;
  }
}
