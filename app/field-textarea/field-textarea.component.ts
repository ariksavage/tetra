import { Component, ViewChild } from '@angular/core';
import { FieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { EditorComponent, TINYMCE_SCRIPT_SRC } from '@tinymce/tinymce-angular';

@Component({
  selector: '.field.textarea',
  standalone: true,
  imports: [ CommonModule, FormsModule, EditorComponent],
  providers: [
    { provide: TINYMCE_SCRIPT_SRC, useValue: 'tinymce/tinymce.min.js' }
  ],
  templateUrl: './field-textarea.component.html',
  styleUrl: '../field/field.component.scss'
})
export class FieldTextAreaComponent extends FieldComponent {
  @ViewChild('area') area: any;
  heightBounce: any = null;

  override ngOnInit() {
    const self = this;
    setTimeout(function(){
      self.autoSize();
    }, 200);
  }

  /**
   * Ensure the textarea is at least tall enough to display all content
   */
  autoSize()
  {
    const self = this;
    clearTimeout(this.heightBounce);
    this.heightBounce = setTimeout(function(){
      const el = self.area.nativeElement;
      el.style.height = 0;
      el.style.height = el.scrollHeight + 'px';
    }, 100);
  }

  change() {
    this.autoSize();
    this.update();
  }
}
