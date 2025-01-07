
import { Component } from '@angular/core';
 import { CommonModule } from '@angular/common';
import { TetraPage } from '@tetra/page/page.component';

@Component({
  standalone: true,
  imports: [CommonModule],
  templateUrl: './install.page.html',
  styleUrl: './install.page.scss'
})

export class InstallPage extends TetraPage {
  override title = 'Tetra Install';
  coreTested: boolean = false;
  coreFound: boolean = false;
  tablesTested: boolean = false;
  tablesFound: boolean = false;
  usersTested: boolean = false;
  usersFound: boolean = false;
  installComplete: boolean = false;

  override onLoad() {
    return this.testCore();
  }

  testCore() {
    const self = this;
    return this.core.get('tetra', 'install').then((response: any) => {
      self.coreTested = true;
      if (response) {
        self.coreFound = true;
      }
    });
  }

  proxyConf() {
    return `{
  "/core/**/*": {
    "target": "https://127.0.0.1:60001",
    "secure": false,
    "logLevel": "debug",
    "changeOrigin": true
  }
}`;
  }
}
