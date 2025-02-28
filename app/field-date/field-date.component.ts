import { Component, Input, Output, EventEmitter } from '@angular/core';
import { FieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
		standalone: true,
  selector: '.field.date',
  standalone: true,
  imports: [ CommonModule, FormsModule ],
  templateUrl: './field-date.component.html',
  styleUrl: './field-date.component.scss'
})
export class TetraFieldDateComponent {
  @Input() label: string = '';
  @Input() model: any = null;
  month: number|null = null;
  date: number|null = null;
  year: number|null = null;
  temp: string = '';
  @Input() placeholder: string = '';
  @Output() modelChange = new EventEmitter<string>();
  error: string = '';

  ngOnInit() {
    if (this.model){
      const d = new Date(this.model);
      this.month = d.getMonth() + 1;
      this.date = d.getDate();
      this.year = d.getFullYear();
    }
  }

  update() {
    let d = '';
    if (this.year){
      d += this.year.toString();
    }
    d += '-';
    if (this.month){
      d += this.month.toString().padStart(2, '0');
    }
    d += '-';
    if (this.date){
      d += this.date.toString().padStart(2, '0');
    }
    d += 'T00:00:00';
    this.modelChange.emit(d);
  }
}
