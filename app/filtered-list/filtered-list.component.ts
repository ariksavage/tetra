import { Component, Input, Output, EventEmitter, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TetraSpinnerComponent } from '../spinner/spinner.component';

@Component({
  selector: '.filtered-list',
  standalone: true,
  imports: [CommonModule, FormsModule, TetraSpinnerComponent],
  templateUrl: './filtered-list.component.html',
  styleUrl: './filtered-list.component.scss'
})
export class TetraFilteredListComponent implements OnInit {
  loaded: boolean = false;
  @Output() load = new EventEmitter<number>();
  @Input() items: Array<any> = [];
  @Input() pagination: any = {};
  @Output() paginationChange = new EventEmitter<any>();
  @Input() singular: string = 'item';
  @Input() plural: string = 'items';
  page: number = 1;
  per: number = 20


  refresh() {
    const self = this;
    if (this.pagination) {
      this.pagination.loading = true;
    }
    this.paginationChange.emit(this.pagination);
    setTimeout(function() {
      self.load.emit(self.page);
    }, 100);
  }

  loading() {
    if (this.pagination && this.pagination.total_results == 0) {
      return false;
    }
    return (!this.pagination || this.pagination.loading);
  }

  ngOnInit() {
    this.refresh();
  }

  setPage(pg: number) {
    const self = this;
    this.pagination.current_page = pg;
    setTimeout(function(){
      self.refresh();
    }, 10);
  }

  summary() {
    let str = 'No ' + this.plural + ' found';

    if (this.pagination && this.pagination.total_results) {
      let per = Math.min(this.pagination.per_page, this.pagination.total_results);
      let n = ((this.pagination.current_page - 1) * per) + 1;
      let z = n + per - 1;
      str = 'Showing ' + n + ' - ' + z + ' of ' + this.pagination.total_results + ' ' + this.plural;
    }
    return str;
  }
}
