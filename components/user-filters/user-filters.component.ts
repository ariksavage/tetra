import { Component, Input, Output, EventEmitter } from '@angular/core';
import { FieldTextComponent } from '@tetra/components/field-text/field-text.component';
import { FieldSelectComponent } from '@tetra/components/field-select/field-select.component';
import { FieldRadiosComponent } from '@tetra/components/field-radios/field-radios.component';
import { FieldMultiSelectComponent } from '@tetra/components/field-multi-select/field-multi-select.component';
import { CoreService } from '@tetra/services/core.service';
import { CommonModule } from '@angular/common';

@Component({
  selector: '.user-filters',
  standalone: true,
  imports: [CommonModule, FieldTextComponent, FieldSelectComponent, FieldRadiosComponent, FieldMultiSelectComponent],
  templateUrl: './user-filters.component.html',
  styleUrl: './user-filters.component.scss'
})
export class UserFiltersComponent {
  @Input() filters: any = {};
  @Output() filtersChange:EventEmitter<any> = new EventEmitter<any>();
  @Output() change:EventEmitter<any> = new EventEmitter<any>();
  @Input() includeCategories: boolean = true;
  sortBy = 'last_name+ASC';
  sortOptions: Array<any> = [
    {
      label: 'Newest Account',
      sort_by: 'date_created',
      sort_dir: 'DESC'
    },
    {
      label: 'Oldest Account',
      sort_by: 'date_created',
      sort_dir: 'ASC'
    },
    {
      label: 'Last Name',
      sort_by: 'last_name',
      sort_dir: 'ASC'
    },
    {
      label: 'First Name',
      sort_by: 'first_name',
      sort_dir: 'ASC'
    },
    {
      label: 'Date Last Accessed',
      sort_by: 'date_created',
      sort_dir: 'DESC'
    }
  ];
  statusOptions: Array<any> = [
    {
      value: '',
      label: 'All'
    },
    {
      value: 'active',
      label: 'Active'
    },
    {
      value: 'pending',
      label: 'Pending Activation'
    },
    {
      value: 'deactivated',
      label: 'Deactivated'
    }
  ];
  roles: Array<any> = [];
  filterRoles: Array<any> = [];
  groups: Array<any> = [];
  filterGroups: Array<any> = [];
  categories: Array<any> = [];
  filterCategories: Array<any> = [];
  ready: boolean = false;


  constructor(
    public core: CoreService,
    ){}

  ngOnInit() {
    if (!this.filters.status){
      this.filters.status = '';
    }
    this.setSort();
    this.core.get('users', 'roles').then((data: any) => {
      this.roles = data.roles;

      this.core.get('users', 'groups').then((data: any) => {
        this.groups = data.groups;

        this.core.get('users', 'categories').then((data: any) => {
          this.categories = data.categories;
          this.ready = true;
        });
      });
    });
  }
  setSort() {
    this.filters.orderBy = this.sortBy.split('+')[0];
    this.filters.orderDir = this.sortBy.split('+')[1];
    this.update();
  }

  setRoles() {
    this.filters.roles = this.filterRoles.map(item => item.id);
    this.update();
  }

  setGroups() {
    this.filters.groups = this.filterGroups.map(item => item.id);
    this.update();
  }

  setCategories() {
    this.filters.categories = this.filterCategories.map(item => item.id);
    this.update();
  }

  sortValue(item: any) {
    return item.sort_by + '+' + item.sort_dir;
  }
  itemLabel(item: any) {
    return item.label;
  }

  statusValue(item: any) {
    return item.value;
  }
  statusLabel(item: any) {
    return item.label;
  }

  roleLabel(item: any) {
    return item.label;
  }
  roleValue(item: any) {
    return item.id;
  }

  categoryLabel(item: any) {
    return item.label;
  }
  categoryValue(item: any) {
    return item.id;
  }

  update() {
    if (this.ready) {
      this.filtersChange.emit(this.filters);
      this.change.emit();
    }
  }
}
