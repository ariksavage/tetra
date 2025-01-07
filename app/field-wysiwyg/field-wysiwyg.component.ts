import { Component, ViewChild } from '@angular/core';
import { FieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { EditorComponent, TINYMCE_SCRIPT_SRC } from '@tinymce/tinymce-angular';

@Component({
  selector: '.field.wysiwyg',
  standalone: true,
  imports: [ CommonModule, FormsModule, EditorComponent],
  providers: [
    { provide: TINYMCE_SCRIPT_SRC, useValue: 'tinymce/tinymce.min.js' }
  ],
  templateUrl: './field-wysiwyg.component.html',
  styleUrl: '../field/field.component.scss'
})
export class FieldWYSIWYGComponent extends FieldComponent {
  @ViewChild('area') area: any;
  heightBounce: any = null;
  init: EditorComponent['init'] = {
    apiKey: '1bm2nxqqyrs1zzd3bt0a47r8356jalk7qwjhss0t36r7rpkv',
    plugins: 'anchor autolink autoresize autosave code emoticons fullscreen help image importcss insertdatetime link lists media preview save searchreplace table wordcount',
    toolbar: 'undo redo | format removeformat | styles | bold italic underline | link hr | forecolor backcolor | alignleft aligncenter alignright alignjustify | outdent indent | code',
  };

  override ngOnInit() {
    const self = this;
    setTimeout(function(){
      // self.autoSize();
    }, 200);
  }

  /**
   * Ensure the textarea is at least tall enough to display all content
   */
  // autoSize()
  // {
  //   const self = this;
  //   clearTimeout(this.heightBounce);
  //   this.heightBounce = setTimeout(function(){
  //     const el = self.area.nativeElement;
  //     el.style.height = 0;
  //     el.style.height = el.scrollHeight + 'px';
  //   }, 100);
  // }

  change() {
    // this.autoSize();
    this.update();
  }
}
