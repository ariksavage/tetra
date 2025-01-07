
import { Component } from '@angular/core';
 import { CommonModule } from '@angular/common';
import { TetraPage } from '@tetra/page/page.component';
import { FieldTextComponent } from '@tetra/field-text/field-text.component';
import { FieldPasswordComponent } from '@tetra/field-password/field-password.component';

@Component({
  standalone: true,
  imports: [CommonModule, FieldTextComponent, FieldPasswordComponent],
  templateUrl: './install.page.html',
  styleUrl: './install.page.scss'
})

export class InstallPage extends TetraPage {
  override title = 'Tetra Install';
  coreTested: boolean = false;
  coreFound: boolean = false;
  dbTested: boolean = false;
  dbFound: boolean = false;
  databaseConfig: any = {
    "host": "",
    "user": "",
    "password": "",
    "db": ""
  };
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
    return this.core.get('tetra', 'core').then((response: any) => {
      self.coreTested = true;
      if (response) {
        self.coreFound = true;
        self.testDB();
      }
    });
  }

  testDB() {
    const self = this;
    return this.core.get('tetra', 'db-test').then((response: any) => {
      console.log('db test response', response);
      self.dbTested = true;
      if (response) {
        self.dbFound = true;
      } else {
        self.core.get('tetra', 'ddev-config').then((config: any) => {
          console.log('config', config);
          if (config.database) {
            self.databaseConfig = config.database;
          }
        });
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
