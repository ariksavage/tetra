import { Model } from '@tetra/model';

export class User extends Model {
  public username!: string;
  public name_prefix!: string;
  public first_name!: string;
  public middle_name!: string;
  public last_name!: string;
  public name_suffix!: string;
  public email!: string;

  public roles: Array<any> = [];

  constructor(data: any = {}) {
    super(data);
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
}
