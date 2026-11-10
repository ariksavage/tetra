import { Component, Input, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { SpinnerComponent } from '@tetra/components/spinner/spinner.component';

@Component({
  selector: 'div.list',
  standalone: true,
  imports: [ CommonModule, SpinnerComponent ],
  templateUrl: './list.component.html',
  styleUrl: './list.component.scss'
})
export class ListComponent {
  @Input() items: Array<any> = [];
  @Input() titleFunc?: Function;

  @Input() onClick: any;
  @Input() icon: string = '';
  @Input() thumbnailFunc?: Function;
  @Input() iconFunc?: Function;
  @Input() itemClass?: Function;
  @Input() header: string = '';
  @Input() itemActive?: Function;
  @Input() loading: boolean = false;
  @Input() noResultText: string = 'No items found.';
  @Input() loadingText: string = 'Loading...';

  getItemClass(item: any) {
    let cl = '';
    if (this.onClick && (!this.itemActive || this.itemActive(item))){
      cl += ' clickable';
    }
    if (this.itemActive && !this.itemActive(item)){
      cl += ' inactive';
    }
    if (this.itemClass){
      cl += ' ' + this.itemClass(item);
    }
    return cl.trim();
  }

}
