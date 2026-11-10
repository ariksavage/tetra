import { Component, ViewChild, ChangeDetectionStrategy } from '@angular/core';
import { FieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
    selector: '.field.textarea',
    imports: [CommonModule, FormsModule],
    providers: [],
    templateUrl: './field-textarea.component.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './field-textarea.component.scss'
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
