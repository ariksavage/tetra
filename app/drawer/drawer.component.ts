import { Component, Input, Output, EventEmitter, ViewChild, ElementRef } from '@angular/core';
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
  @Output() onToggle: EventEmitter<any> = new EventEmitter<any>();
  open: boolean = false;
  transition: boolean = false;
  id: string = '';
  @ViewChild('drawerContent') content: ElementRef = {} as ElementRef;
  @ViewChild('drawerBackdrop') backdrop: ElementRef = {} as ElementRef;

  ngOnInit(){
    this.id = 'drawer-';
    if (this.title){
      this.id += this.title.toLowerCase().replace(' ', '-');
    } else {
      this.id += this.randStr(7);
    }
  }

  ngAfterViewInit() {
    // Move elements up to main to prevent constraint and weird nesting
    const mainContent = window.document.getElementsByClassName('main-content')[0];
    mainContent.appendChild(this.content.nativeElement);
    mainContent.appendChild(this.backdrop.nativeElement);
  }
  randStr(length: number) {
    let result = '';
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const charactersLength = characters.length;
    let counter = 0;
    while (counter < length) {
      result += characters.charAt(Math.floor(Math.random() * charactersLength));
      counter += 1;
    }
    return result;
  }

  toggle() {
    const self = this;
    if (this.open) {
      this.open = false;
      setTimeout(function() {
        self.transition = false;
        self.onToggle.emit();
      }, 500);
    } else {
      this.transition = true;
      setTimeout(function() {
        self.open = true;
        self.onToggle.emit();
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
