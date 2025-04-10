import { Component, Input, Output, EventEmitter, ViewChild, ElementRef } from '@angular/core';
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
  @Output() openChange: EventEmitter<boolean> = new EventEmitter<boolean>();
  id: string = '';
  @Output() onClose: EventEmitter<any> = new EventEmitter<any>();
  @ViewChild('popupWindow') popupWindow: ElementRef = {} as ElementRef;
  @ViewChild('popupBackDrop') backdrop: ElementRef = {} as ElementRef;
  @Input() button: boolean = true;

  ngOnInit(){
    this.id = 'popup-';
    if (this.title){
      this.id += this.title.toLowerCase().replace(' ', '-');
    } else {
      this.id += this.randStr(7);
    }
  }

  ngAfterViewInit() {
    // Move elements up to main to prevent constraint and weird nesting
    const mainContent = window.document.getElementsByClassName('main-content')[0];
    mainContent.appendChild(this.popupWindow.nativeElement);
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
    this.open = !this.open;
    this.openChange.emit(this.open);
    if (!this.open) {
      this.onClose.emit();
    }
  }
}
