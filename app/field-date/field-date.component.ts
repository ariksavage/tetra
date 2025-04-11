import { Component, Input, Output, EventEmitter, OnInit } from '@angular/core';
// import { TetraFieldComponent } from '@tetra/field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
	standalone: true,
  selector: '.field.date',
  imports: [ CommonModule, FormsModule ],
  templateUrl: './field-date.component.html',
  styleUrl: './field-date.component.scss'
})
export class TetraFieldDateComponent implements OnInit{
  @Input() label: string = '';
  @Input() model: any = null;
  month: number|null = null;
  date: number|null = null;
  year: number|null = null;
  temp: string = '';
  @Input() placeholder: string = '';
  @Output() modelChange = new EventEmitter<any>();
  error: string = '';

  ngOnInit() {
    if (this.model){
      const d = new Date(this.model);
      this.month = d.getMonth() + 1;
      this.date = d.getDate();
      this.year = d.getFullYear();
      this.daysInMonth();
    } else {
      setTimeout(() => {
        this.ngOnInit()
      }, 100);
    }
  }

  daysInMonth() {
    let d = 31;
    if (this.month) {
      switch(this.month.toString()) {
        case '2': // February
          if (this.year){
            d = this.year % 4 == 0 ? 29 : 28;
          } else {
            d = 28;
          }
          break;
        case '9':  // hath September
        case '4':  // April
        case '6':  // June
        case '11': // and November
          d = 30;
          break;
        default:
          d = 31;
          break;
      }
    }
    if (this.date && this.date > d){
      this.date = d;
    }
    return d;
  }

  update() {
    this.daysInMonth();
    setTimeout(() =>{
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
      this.modelChange.emit(new Date(d).toUTCString());
    }, 100);
  }
}
