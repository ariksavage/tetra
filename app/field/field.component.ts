import { Component, Input, Output, EventEmitter, OnInit, ChangeDetectorRef } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
  standalone: true,
  imports: [ CommonModule, FormsModule ],
  templateUrl: './field.component.html',
  styleUrl: './field.component.scss'
})
export class FieldComponent implements OnInit {
  @Input() label: string = '';
  @Input() model: any = null;
  @Input() placeholder: string = '';
  @Output() modelChange = new EventEmitter<string>();
  @Output() afterChange = new EventEmitter<boolean>();
  type: string = 'text';
  error: string = '';
  hasFocus: boolean = false;
  touched: boolean = false;
  updateDebounce: any = null;
  @Output() onEnter = new EventEmitter<any>();

  ngOnInit() {
  }

  setError(str: string) {
    this.error = str;
  }
  getError() {
    return this.error;
  }

  id() {
    return this.type + '-' + this.label.toLowerCase().replace(/[^a-z0-9]+/, '-');
  }

  update() {
    const self = this;
    clearTimeout(this.updateDebounce);
    this.updateDebounce = setTimeout(function() {
      self.touched = true;
      self.modelChange.emit(self.model);
      self.afterChange.emit(true);
    }, 100);
  }

  onChange(e: any) {
    this.update();
  }

  onKeyDown(e: any) {
    this.update();
    if (e.key == 'Enter') {
      this.onEnter.emit();
    }
  }

  onFocus(e: any) {
    this.hasFocus = true;
  }

  onBlur(e: any) {
    this.hasFocus = false;
  }
}
