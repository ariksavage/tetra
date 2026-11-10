import { TestBed } from '@angular/core/testing';
import { Component, forwardRef, Input } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, NG_VALUE_ACCESSOR } from '@angular/forms';
import { FieldWysiwygComponent, WysiwygStyle } from './field-wysiwyg.component';
import { EditorComponent } from '@tinymce/tinymce-angular';

// Mock TinyMCE EditorComponent
@Component({
  selector: 'editor',
  standalone: true,
  template: '<div></div>',
  providers: [{
    provide: NG_VALUE_ACCESSOR,
    useExisting: forwardRef(() => MockEditorComponent),
    multi: true
  }]
})
class MockEditorComponent {
  @Input() id: string = '';
  @Input() init: any = {};
  @Input() ngModel: any;
  writeValue() {}
  registerOnChange() {}
  registerOnTouched() {}
}

describe('FieldWysiwygComponent', () => {
  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FieldWysiwygComponent, CommonModule, FormsModule],
    })
    .overrideComponent(FieldWysiwygComponent, {
      remove: { imports: [EditorComponent] },
      add: { imports: [MockEditorComponent] },
    })
    .compileComponents();
  });

  it('should create', () => {
    const fixture = TestBed.createComponent(FieldWysiwygComponent);
    fixture.detectChanges();
    expect(fixture.componentInstance).toBeTruthy();
  });

  it('should build editor config with style formats', () => {
    const fixture = TestBed.createComponent(FieldWysiwygComponent);
    fixture.componentInstance.styles = [
      { title: 'Ingredient', classes: 'ingredient', css: 'color: red;' }
    ];
    fixture.componentInstance.ngOnChanges({
      styles: { currentValue: fixture.componentInstance.styles, previousValue: [], firstChange: false, isFirstChange: () => false }
    });
    expect(fixture.componentInstance.editorConfig.style_formats).toHaveLength(1);
    expect(fixture.componentInstance.editorConfig.style_formats[0].title).toBe('Ingredient');
  });

  it('should inject css into content_style', () => {
    const fixture = TestBed.createComponent(FieldWysiwygComponent);
    fixture.componentInstance.styles = [
      { title: 'Ingredient', classes: 'ingredient', css: 'color: red;' }
    ];
    fixture.componentInstance.ngOnChanges({
      styles: { currentValue: fixture.componentInstance.styles, previousValue: [], firstChange: false, isFirstChange: () => false }
    });
    expect(fixture.componentInstance.editorConfig.content_style).toContain('.ingredient { color: red; }');
  });

  it('should update model on editor change', () => {
    const fixture = TestBed.createComponent(FieldWysiwygComponent);
    fixture.componentInstance.onEditorChange('<p>hello</p>');
    expect(fixture.componentInstance.model).toBe('<p>hello</p>');
  });
});
