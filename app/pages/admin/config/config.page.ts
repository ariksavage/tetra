import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TetraPage } from '@tetra/page/page.component';
import { TetraFieldNumberComponent } from '@tetra/field-number/field-number.component';
import { TetraFieldTextComponent } from '@tetra/field-text/field-text.component';
import { Config } from '@tetra/config';
import { TetraButtonComponent } from '@tetra/button/button.component';


@Component({
  selector: "AdminConfigPage",
  standalone: true,
  imports: [
    TetraButtonComponent,
    CommonModule, TetraFieldNumberComponent, TetraFieldTextComponent, TetraButtonComponent ],
  templateUrl: './config.page.html',
  styleUrl: './config.page.scss',
})

export class AdminConfigPage extends TetraPage {
  override title = 'Config';
  config: Array<Config> = [];

  override onLoad() {
    const self = this;
    this.core.post('config', 'list').then((data: any) => {

      self.config = data.config.map((item: any) => {
        return new Config(item);
      });
    });
  }

  reset( item: any ) {
    item.value = item.original;
  }
  update( item: any ) {
    const value = item.value;
    const data = {value};
    this.core.patch('config', 'value', item.id, data).then((config: Config) => {
    })
  }
}
