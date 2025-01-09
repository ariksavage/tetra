import { Model } from '@tetra/model';

export class User extends Model {
  public username: any;
  public name_prefix: any;
  public first_name: any;
  public middle_name: any;
  public last_name: any;
  public name_suffix: any;
  public email: any;
  public roles: Array<any> = [];

  constructor(data: any = null) {
    super(data);
  }

  override mapData(data: any) {
    Object.assign(this, data);
  }

  name() {
    const names = [];
    if (this.name_prefix){
      names.push(this.name_prefix);
    }
    if (this.first_name){
      names.push(this.first_name);
    }
    if (this.middle_name){
      names.push(this.middle_name);
    }
    if (this.last_name){
      names.push(this.last_name);
    }
    if (this.name_suffix){
      names.push(this.name_suffix);
    }
    return names.join(' ');
  }
}
