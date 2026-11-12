import { Component, Input, HostBinding, OnChanges, OnInit, SimpleChanges, ChangeDetectionStrategy, ElementRef, forwardRef, inject } from '@angular/core';
import { FieldComponent } from '../field/field.component';
import { ControlValueAccessor, FormsModule, NG_VALUE_ACCESSOR } from '@angular/forms';
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
        },
        // Also works as a form control: [(ngModel)] as well as [(model)].
        { provide: NG_VALUE_ACCESSOR, useExisting: forwardRef(() => FieldWysiwygComponent), multi: true }
    ],
    templateUrl: './field-wysiwyg.component.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './field-wysiwyg.component.scss'
})
export class FieldWysiwygComponent extends FieldComponent implements OnInit, OnChanges, ControlValueAccessor {

  @Input() styles: WysiwygStyle[] = [];

  /**
   * 'full' (the default): formatting styles and the source view.
   * 'simple': bold, italic, lists and links, for short notes and messages.
   * Or a TinyMCE toolbar string of your own.
   */
  @Input() toolbar: 'full' | 'simple' | string = 'full';

  /**
   * Boxed: always looks like an input (the theme's --input-border and
   * --input-background) with its toolbar showing, instead of only on hover.
   */
  @HostBinding('class.boxed')
  @Input() boxed: boolean = false;

  /** The editor's accessible name, when its label is outside the field. */
  @Input() ariaLabel: string = '';

  @HostBinding('class.no-label') get noLabel() { return !this.label || this.inline; }

  private host = inject(ElementRef<HTMLElement>);
  private onChangeFn: (value: string) => void = () => {};
  private onTouchedFn: () => void = () => {};

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
    this.onTouchedFn();
  }

  // ---- ControlValueAccessor ----

  writeValue(value: any) {
    this.model = value ?? '';
  }

  registerOnChange(fn: (value: string) => void) {
    this.onChangeFn = fn;
  }

  registerOnTouched(fn: () => void) {
    this.onTouchedFn = fn;
  }

  override ngOnInit() {
    this.buildEditorConfig();
  }

  ngOnChanges(changes: SimpleChanges) {
    if (changes['styles'] || changes['inline'] || changes['label'] || changes['toolbar'] || changes['ariaLabel']) {
      this.buildEditorConfig();
    }
  }

  /**
   * The editor's content sits in its own frame, which the page's CSS doesn't
   * reach, so it copies the field's font and colours from the page.
   */
  private buildContentStyle(): string {
    const customStyles = this.styles
      .filter(s => s.css)
      .map(s => `.${s.classes} { ${s.css} }`)
      .join('\n');

    let page = '';
    const el: HTMLElement | undefined = this.host?.nativeElement;
    if (el && typeof getComputedStyle === 'function') {
      const css = getComputedStyle(el);
      const value = (name: string) => css.getPropertyValue(name).trim();
      const color = value('--input-text') || css.color;
      const link = value('--link') || value('--primary');
      page = `
    body { font-family: ${css.fontFamily}; font-size: ${css.fontSize}; line-height: ${value('--input-line-height') || css.lineHeight}; color: ${color}; margin: ${value('--input-pad-y') || '.35em'} ${value('--input-pad-x') || '.5em'}; }
    p, ul, ol { margin: 0 0 .75em; }
    ${link ? `a { color: ${link}; }` : ''}`;
    }

    return `
    body { background: transparent; transition: background 0.2s ease; }${page}
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

    const simple = this.toolbar === 'simple';
    const toolbar = simple ? 'bold italic | bullist numlist | link | undo redo'
      : this.toolbar === 'full' || !this.toolbar ? 'undo redo | bold italic underline | styles | code'
      : this.toolbar;

    const common = {
      license_key: 'gpl',
      toolbar,
      // Pasted text keeps its paragraphs and lists, not another site's fonts and colours.
      paste_as_text: false,
      paste_remove_styles_if_webkit: true,
      invalid_styles: 'color font-family font-size background background-color',
      link_default_target: '_blank',
      link_target_list: false,
      ...(this.ariaLabel && { iframe_aria_text: this.ariaLabel }),
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
      plugins: simple ? 'lists link autolink autoresize' : 'lists link image table code wordcount autoresize',
      min_height: simple ? 200 : 150,
      max_height: simple ? 500 : 200,
      autoresize_bottom_margin: 24,
    };
  }

  onEditorChange(value: string) {
    this.model = value;
    this.onChangeFn(value);
    this.update();
  }
}
