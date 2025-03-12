import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TetraPage } from '@tetra/page/page.component';

import { TetraEditMenuItemComponent } from '@tetra/edit-menu-item/edit-menu-item.component';


@Component({
		standalone: true,
    selector: "AdminConfigMenuPage",
    imports: [CommonModule, TetraEditMenuItemComponent],
    templateUrl: './menu.page.html',
    styleUrl: './menu.page.scss'
})

export class AdminConfigMenuPage extends TetraPage {
  override title = 'Menu';
  menu: any = null;

  override onLoad() {
    const self = this;
    this.getMenu();
  }

  getMenu(rootPath: string = '') {
    const self = this;
    return this.core.get('app', 'menu-tree', rootPath).then((data) => {
      this.menu = data.menu;
    })
  }

  editIcon(item: any) {
    item.editIcon = true;
  }
  removeItem(item: any) {
    const i = this.menu.children.indexOf(item);
    this.menu.children.splice(i, 1);
  }
  addItem() {
    this.menu.children.push({
      title: 'New Item',
      path: '/',
      icon: 'fa-question',
      weight: this.menu.children.length,
      children: []
    })
  }

  updateMenu() {
    console.log(this.menu)
    const data = {
      menu: this.menu
    };
    this.core.patch(data, 'config', 'menu').then((data: any) => {
      window.location.reload();
    })
  }
}
