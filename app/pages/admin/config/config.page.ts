import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TetraPage } from '@tetra/page/page.component';


@Component({
  selector: "AdminConfigPage",
  standalone: true,
  imports: [ CommonModule],
  templateUrl: './config.page.html',
  styleUrl: './config.page.scss',
})

export class AdminConfigPage extends TetraPage {
  override title = 'Config';

  override onLoad() {
    const self = this;
  }
}
