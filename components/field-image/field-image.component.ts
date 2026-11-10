import { Component, Input, Output, EventEmitter, ChangeDetectionStrategy } from '@angular/core';
import { FieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
    selector: '.field.image',
    imports: [CommonModule, FormsModule],
    templateUrl: './field-image.component.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './field-image.component.scss'
})
export class FieldImageComponent extends FieldComponent {
  override type = 'file';
  previewUrl: string | null = null;
  @Output() handleTemp = new EventEmitter<any>();
  @Input() showPreview: boolean = true;
  @Input() alt: string = '';


  override onChangeEvent(e: Event): void {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];

    if (file) {
      this.model = file;

      // Revoke previous object URL to avoid memory leaks
      if (this.previewUrl) {
        URL.revokeObjectURL(this.previewUrl);
      }
      this.previewUrl = URL.createObjectURL(file);
      this.model = this.previewUrl;
    } else {
      this.model = null;
      this.previewUrl = null;
    }

    // this.update();
    this.modelChange.emit(file);
  }

  /**
   * Drop the local preview when the parent clears the model, eg. after a failed upload.
   */
  ngOnChanges(): void {
    if (!this.model && this.previewUrl) {
      URL.revokeObjectURL(this.previewUrl);
      this.previewUrl = null;
    }
  }

  override modelExists(): boolean {
    return this.model instanceof File;
  }

  override beforeModelChange() {
    if (this.handleTemp){
      this.handleTemp.emit(this.model);
    }
    return this.model;
  }

  ngOnDestroy(): void {
    if (this.previewUrl) {
      URL.revokeObjectURL(this.previewUrl);
    }
  }
}
