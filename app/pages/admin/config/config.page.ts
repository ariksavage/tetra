import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TetraPage } from '@tetra/page/page.component';
import { TetraFieldNumberComponent } from '@tetra/field-number/field-number.component';
import { TetraFieldTextComponent } from '@tetra/field-text/field-text.component';
import { Config } from '@tetra/config';
import { TetraButtonComponent } from '@tetra/button/button.component';
import { SectionCollapseComponent } from '@tetra/section-collapse/section-collapse.component';

@Component({
		standalone: true,
    selector: "AdminConfigPage",
    imports: [
        TetraButtonComponent,
        CommonModule, TetraFieldNumberComponent, TetraFieldTextComponent, TetraButtonComponent, SectionCollapseComponent
    ],
    templateUrl: './config.page.html',
    styleUrl: './config.page.scss'
})

export class AdminConfigPage extends TetraPage {
  override title = 'Config';
  config: Array<Config> = [];
  categories: Array<string> = [];
  override pageConfig: any = {
    showHeader: true,
    titleInHeader: false,
    showMenu: true,
  };

  override onLoad() {
    const self = this;
    this.core.get('config', 'list').then((data: any) => {
      this.config = data.config.map((item: Config) => {
        if (!(this.categories.indexOf(item.type) > -1)) {
          this.categories.push(item.type);
        }
        return new Config(item);
      })
      this.categories = this.categories.sort();
    });
  }

  byCategory(category: string) {
    return this.config.filter((item: Config) => {
      return item.type == category;
    });
  }

  categoryLabel(category: string) {
    return category.split('_').join(' ').toUpperCase();
  }

  reset( item: any ) {
    item.value = item.original;
  }
  update( item: any ) {
    const value = item.value;
    const data = {value};
    console.log('update', data);
    this.core.patch(data, 'config', 'value', item.id).then((config: Config) => {
    })
  }
}
