import { Model } from '@tetra/classes/model';

export class User extends Model {
  public username!: string;
  public name_prefix!: string;
  public first_name!: string;
  public middle_name!: string;
  public last_name!: string;
  public name_suffix!: string;
  public email!: string;
  public roles: any = null;
  public category: any = null;
  public category_id!: number;
  public user_groups: Array<any> = [];
  public profile_pic_uri!: string;
  public active!: boolean;
  public status!: string;
  public phone_number!: string;
  public address_1!: string;
  public address_2!: string;
  public city!: string;
  public state!: string;
  public zip!: string;
  public country!: string;
  public data!: object;
  public lang!: string;

  constructor(data: any = {}) {
    super(data);
    if (this.category && !this.category_id){
      this.category_id = this.category.id;
    }
    data = this.parseDates(data);
    this.mapData(data);
  }

  name() {
    const names = [];
    if (this.name_prefix) {
      names.push(this.name_prefix);
    }
    if (this.first_name) {
      names.push(this.first_name);
    }
    if (this.middle_name) {
      names.push(this.middle_name);
    }
    if (this.last_name) {
      names.push(this.last_name);
    }
    if (this.name_suffix) {
      names.push(this.name_suffix);
    }
    return names.join(' ');
  }
    getRoles() {
    let roles: Array<any> = [];
    for (var label in this.roles) {
      let role = this.roles[label];
      role.label = this.toTitleCase(role.label);
      roles.push(role);
    }
    return roles;
  }

  /**
   * Test if any of this user's roles grants a permission.
   * Mirrors UserRole::hasPermission on the server, which remains
   * the real enforcement; this is for showing/hiding UI.
   *
   * - '*' grants every action in the dimension
   * - no action: any permission in the dimension is enough
   * - 'X OWN' is satisfied by 'X'
   */
  hasPermission(dimension: string, action: string = ''): boolean {
    const roles = this.roles ? Object.values(this.roles) : [];
    return roles.some((role: any) => {
      const permissions = role?.permissions?.[dimension];
      if (!permissions || !Object.keys(permissions).length) {
        return false;
      }
      if (!action || permissions['*']) {
        return true;
      }
      if (action.includes(' OWN') && permissions[action.replace(' OWN', '')]) {
        return true;
      }
      return !!permissions[action];
    });
  }

  userStatus() {
    return this.toTitleCase(this.status);
  }
  userStatusIcon() {
    let cl = '';
    switch(this.status) {
    case 'deactivated':
      cl = 'fa-ban';
      break;
    case 'pending':
      cl = 'fa-info';
      break;
    case 'active':
      cl = 'fa-check';
      break;
    }
    return cl;
  }
}
