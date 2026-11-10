import { Component, ChangeDetectionStrategy } from '@angular/core';

import { FieldTextComponent } from '@tetra/components/field-text/field-text.component';
import { FieldPasswordComponent } from '@tetra/components/field-password/field-password.component';
import { FieldEmailComponent } from '@tetra/components/field-email/field-email.component';
import { Page } from '@tetra/pages/page/page.component';
import { Config } from '@tetra/classes/config';

@Component({
    selector: 'install-page',
    imports: [FieldTextComponent, FieldPasswordComponent, FieldEmailComponent],
    templateUrl: './install.page.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './install.page.scss'
})
/**
 * Tetra Application install Page
 */
export class InstallPageComponent extends Page {
  public override title = 'Install';
  protected override requiresLogin = false;
  public db: any = {
    host: '',
    user: '',
    password: '',
    name: ''
  }
  installKey: string = '';

  /**
   * @ppr
   */
  installationTables = [
    'config',
    'users',
    'users_sessions',
    'permissions',
    'roles',
    'roles_inheritance',
    'roles_permissions',
    'users_roles',
    'user_groups',
    'users_groups',
    'tags',
    'tags_assignments'
  ];
  tablesInstalled: Array<string> = [];
  config: Array<Config> = [];
  rootUser = {
    username: '',
    email: '',
    password: '',
    password2: '',
    first_name: '',
    last_name: ''
  };

  toTitleFromUnderscore(str: string): string {
    return str
      .split('_')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ');
  }

  checkTables(i = 0) {
    if (i == this.installationTables.length) {
      return Promise.resolve();
    }
    const table = this.installationTables[i];

    i++;
    return this.core.get('install', 'table-exists', table).then((response) => {
      if (response.exists) {
        this.tablesInstalled.push(table);
      }
      this.checkTables(i);
    })
  }

  createTables(i = 0) {
    if (i == this.installationTables.length) {
      return Promise.resolve();
    }
    const table = this.installationTables[i];
    const action = table.replace(/_/,'-') + '-table';
    i++;

    return this.core.put('install', action).then((response) => {
      this.tablesInstalled.push(table);
      this.createTables(i);
    })
  }

  validateHost(val: string) {
    return true;
  }
  saveDbReady() {
    return !this.installKey && this.db.host && this.db.user && this.db.password && this.db.name;
  }
  saveDbCredentials() {
    this.core.post('install', 'database', this.db).then((response) => {
      this.installKey = response.key;
      this.core.setInstallKey(this.installKey);
      this.checkTables().then(response => {
        this.getConfig().then(response2 => {
          this.createDefaultRolesPermissions();
        });
      });
    });
  }
  createUsersTable() {
    this.core.put('install', 'users-table').then((response) => {
    });
  }

  getConfig() {
    return this.core.get('install', 'config').then((response) => {
      this.config = response.config.map((data: Config) => {return new Config(data);});
      this.app.fetchAppConfig();
    })
  }

  addDefaultConfig() {
    return this.core.put('install', 'default-config').then((response) => {
      this.getConfig();
    });
  }

  saveDefaultConfig() {
    const payload = {
      config: this.config
    };
    return this.core.post('install', 'config', payload).then((response) =>{
      this.getConfig();
    })
  }

  createDefaultRolesPermissions() {
    return this.core.put('install', 'default-roles-permissions');
  }
  rootUserReady() {
     if (!this.rootUser.username){
      return false;
    }
    if (!this.rootUser.email){
      return false;
    }
    if (!this.rootUser.password){
      return false;
    }
    if (this.rootUser.password !== this.rootUser.password2){
      return false;
    }
    return true;
  }
  createRootUser() {
   
    const payload = {
      root_user: this.rootUser
    }
    return this.core.post('install', 'root-user', payload).then(() => {
      this.route.navigateByUrl('/login');
    });
  }
}
