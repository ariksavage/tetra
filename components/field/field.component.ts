import { Component, Input, Output, EventEmitter, OnInit, ChangeDetectorRef, HostBinding, ChangeDetectionStrategy } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
    imports: [CommonModule, FormsModule],
    templateUrl: './field.component.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './field.component.scss'
})
export class FieldComponent implements OnInit {
  @Input() label: string = '';
  @Input() model: any = null;
  @Input() placeholder: string = '';
  @Output() modelChange: EventEmitter<any> = new EventEmitter<any>();
  @Output() afterChange = new EventEmitter<boolean>();
  @Input() type: string = 'text';
  error: string = '';
  hasFocus: boolean = false;
  touched: boolean = false;
  updateDebounce: any = null;
  @Output() onEnter = new EventEmitter<any>();
  @Output() onFocus = new EventEmitter<any>();
  @Output() onBlur = new EventEmitter<any>();
  @Input() validate: any = null;
  @HostBinding('class.valid') isValid = false;
  @HostBinding('class.invalid') isInvalid = false;
  @Input() required: boolean = false;
  private static nextUid = 0;
  private readonly uid = ++FieldComponent.nextUid;

  ngOnInit() {
    this.onInit();
  }
  onInit() {
    this.beforeModelChange();
  }

  setError(str: string) {
    this.error = str;
  }
  getError() {
    return this.error;
  }

  /**
   * Unique per field, so a label always points at its own input,
   * even when several fields on a page share a label (e.g. every step's title).
   */
  id() {
    const slug = (this.label || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    return [this.type, slug, this.uid].filter(Boolean).join('-');
  }

  beforeModelChange() {
    return this.model;
  }

  update() {
    const self = this;

    clearTimeout(this.updateDebounce);
    this.updateDebounce = setTimeout(function() {
      self.commit();
    }, 100);
  }

  /**
   * Validate and emit the model immediately, skipping the debounce.
   */
  commit() {
    clearTimeout(this.updateDebounce);
    this.touched = true;
    const valid = this.validation();
    this.isValid = valid !== null && valid === true;
    this.isInvalid = valid !== null && valid !== true;
    if (!this.model || valid !== false){
      this.modelChange.emit(this.beforeModelChange());
      this.afterChange.emit(true);
    }
  }

  modelValid(): boolean|null {
    return null;
  }

  validation() {
    if (this.required && !this.modelExists()){
      return false;
    }
    if (this.validate) {
      return this.validate(this.model);
    }
    return this.modelValid();
  }

  modelExists() {
    return !!this.model;
  }

  onFocusClass(){
    return this.modelExists() || this.hasFocus;
  }

  onChangeEvent(e: any) {
    // The value is final at this point (blur or Enter), so hand it over immediately:
    // anything listening for (change) on the field must see the new value, not the old one.
    this.commit();
  }

  onKeyDownEvent(e: any) {
    if (e.key == 'Enter') {
      // Flush the pending model update so listeners see the current value.
      this.commit();
      this.onEnter.emit();
    }
    this.update();
  }

  onFocusEvent(e: any) {
    this.hasFocus = true;
    this.onFocus.emit();
  }

  onBlurEvent(e: any) {
    this.hasFocus = false;
    this.onBlur.emit();
  }
}
