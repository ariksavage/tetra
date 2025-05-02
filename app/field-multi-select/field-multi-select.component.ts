import { Component, Input, Output, EventEmitter, ViewChild, ElementRef } from '@angular/core';
// import { TetraFieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
		standalone: true,
    selector: '.field.multi-select',
    imports: [CommonModule, FormsModule],
    templateUrl: './field-multi-select.component.html',
    styleUrl: './field-multi-select.component.scss'
})
export class TetraFieldMultiSelectComponent {
  @Input() label: string = '';
  @Input() placeholder: string = '';
  @Input() model: any = null;
  @Output() modelChange: EventEmitter<any> = new EventEmitter<any>();
  @Output() afterChange: EventEmitter<any> = new EventEmitter<any>();
  @Input() items: Array<any> = [];
  @Input() titleFunc: any = null;
  @Input() valueFunc: any = null;
  @Input() newFunc: any = null;
  filter: string = '';
  focus: boolean = false;
  blurTO: any = null;
  @Input() allowNew: boolean = true;
  @ViewChild('select', { static: false }) selecDiv: ElementRef = {} as ElementRef;

  id(){
    return 'multi-select-' + this.label.toLowerCase();
  }

  value(item: any) {
    return item;
  }

  filteredItems() {
    return this.items.filter((item: any) => {
      let include = true;
      if (this.filter && this.titleFunc(item).indexOf(this.filter) == -1) {
        include = false;
      } else {
        this.model.forEach((modelItem: any) => {
          if (this.value(modelItem) == this.value(item)){
            include = false;
          }
        })
      }
      return include;
    }).sort((a: any, b: any) => {
      return this.titleFunc(a).localeCompare(this.titleFunc(b));
    });
  }

  matchItem() {
    if (!this.filter){
      return true;
    }
    let matches = this.items.filter(item => this.titleFunc(item) == this.filter);
    return matches.length > 0;
  }

  onFocus() {
    const el =  this.selecDiv.nativeElement;
    const y = el.getBoundingClientRect().y;
    var body = document.body;
    var docEl = document.documentElement;

    // Check if the element is roughly halfway down the page
    // If so, add a class to open the dropdown up
    var scrollTop = window.pageYOffset || docEl.scrollTop || body.scrollTop;
    var t = scrollTop + (window.innerHeight *.4);
    if (y >= t){
      el.className += ' up';
    } else {
      el.className = el.className.replace(' up','').trim()
    }
    clearTimeout(this.blurTO);
    this.focus = true;
  }

  onBlur() {
    this.blurTO = setTimeout(() => {
      this.focus = false;
    }, 10);
  }

  add(item: any){
    this.model.push(this.value(item));
    this.afterChange.emit(this.model);
  }
  remove(i: number) {
    this.model.splice(i, 1);
    this.afterChange.emit(this.model);
  }

  new() {
    this.newFunc(this.filter);
    this.filter = '';
  }
}
