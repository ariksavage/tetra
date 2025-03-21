import { Component, ViewChild } from '@angular/core';
import { TetraFieldComponent } from '@tetra/field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { EditorComponent } from '@tinymce/tinymce-angular';

@Component({
	standalone: true,
  selector: '.field.wysiwyg',
  imports: [ CommonModule, FormsModule, EditorComponent],
  templateUrl: './field-wysiwyg.component.html',
  styleUrl: '../field/field.component.scss'
})
export class FieldWYSIWYGComponent extends TetraFieldComponent {
  @ViewChild('area') area: any;
  heightBounce: any = null;
  apiKey: string = "9pv8noesa604gu64o61wkaos9ce0rkp4qmg3iwhcd8e6mt0y";
  init: EditorComponent['init'] = {
    min_height: 200,
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
