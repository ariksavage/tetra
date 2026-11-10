import { Component, Input, HostBinding, OnChanges, OnInit, SimpleChanges, ChangeDetectionStrategy } from '@angular/core';
import { FieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { EditorComponent, TINYMCE_SCRIPT_SRC } from '@tinymce/tinymce-angular';

export interface WysiwygStyle {
  title: string;
  classes: string;
  inline?: string;
  block?: string;
  css?: string;
}

@Component({
    selector: '.field.wysiwyg',
    imports: [CommonModule, FormsModule, EditorComponent],
    providers: [
        {
            provide: TINYMCE_SCRIPT_SRC,
            // Self-hosted (GPL) build, copied from node_modules/tinymce by angular.json
            useValue: '/tinymce/tinymce.min.js'
        }
    ],
    templateUrl: './field-wysiwyg.component.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './field-wysiwyg.component.scss'
})
export class FieldWysiwygComponent extends FieldComponent implements OnInit, OnChanges {

  @Input() styles: WysiwygStyle[] = [];

  /**
   * Inline mode: edit the content directly in the page, styled by the page's own CSS,
   * instead of inside the editor's framed box. The toolbar shows while the field has focus.
   */
  @HostBinding('class.inline')
  @Input() inline: boolean = false;

  editorConfig: Record<string, any> = {};
  editorId: string = `wysiwyg-${Math.random().toString(36).substring(2, 9)}`;
  toolbarId: string = `${this.editorId}-toolbar`;
  isFocused: boolean = false;

  onEditorFocus() {
    this.isFocused = true;
  }

  onEditorBlur() {
    this.isFocused = false;
  }

  override ngOnInit() {
    this.buildEditorConfig();
  }

  ngOnChanges(changes: SimpleChanges) {
    if (changes['styles'] || changes['inline'] || changes['label']) {
      this.buildEditorConfig();
    }
  }

  private buildContentStyle(): string {
  const customStyles = this.styles
    .filter(s => s.css)
    .map(s => `.${s.classes} { ${s.css} }`)
    .join('\n');

  return `
    body { background: transparent; transition: background 0.2s ease; }
    ${customStyles}
  `;
}

  private buildEditorConfig() {
    const styleFormats = this.styles.map(s => ({
      title: s.title,
      inline: s.block ? undefined : (s.inline ?? 'span'),
      block: s.block ?? undefined,
      classes: s.classes,
    }));

    const common = {
      license_key: 'gpl',
      toolbar: 'undo redo | bold italic underline | styles | code',
      menubar: false,
      branding: false,
      ...(styleFormats.length > 0 && {
        style_formats: styleFormats,
        style_formats_merge: false,
      }),
    };

    if (this.inline) {
      // The page styles the content, so no content_style or sizing here.
      this.editorConfig = {
        ...common,
        plugins: 'lists link code',
        placeholder: this.label,
        // Show the toolbar in its own row under the content, so it never covers what is above.
        fixed_toolbar_container: '#' + this.toolbarId,
      };
      return;
    }

    this.editorConfig = {
      ...common,
      content_style: this.buildContentStyle(),
      plugins: 'lists link image table code wordcount autoresize',
      min_height: 150,
      max_height: 200,
      autoresize_bottom_margin: 24,
    };
  }

  onEditorChange(value: string) {
    this.model = value;
    this.update();
  }
}
