import { Component } from '@angular/core';
import { AppService } from '@tetra/app.service';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'nav.secondary-nav',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './secondary-nav.component.html',
  styleUrl: './secondary-nav.component.scss'
})
export class TetraSecondaryNavComponent {
  items: Array<any> = [];
  constructor(
    private app: AppService,
  ) {
    app.getSecondaryNav().subscribe((navItems: any) => {
      this.items = navItems;
      console.log('items', this.items);
    })
  }

  isCurrent(item: any) {
    return item.path == window.location.pathname;
  }
}
