import { DataObject } from './object';
import { UserGroup } from './user-group';
import { UserCategory } from './user-category';
import { UserRole } from './user-role';

export class User extends DataObject {
  ack_intro_page: boolean = false;
  active_session: boolean = false;
  address_1: string = '';
  address_2: string = '';
  app_theme_id: number|null = null;
  category: any = null;
  category_id: number|null = null;
  change_password: boolean = false;
  city: string = '';
  country: string = '';
  data: string|null = null;
  date_last_accessed: Date|null = null;
  date_of_birth: Date|null = null;
  first_name: string = '';
  from_import: boolean = false;
  groups: Array<any> = [];
  has_entity_roles: boolean = false;
  lang: string = 'en_US';
  last_name: string = '';
  middle_name: string = '';
  name_suffix: string = '';
  notify_activation: boolean = false;
  phone_number: string = '';
  primary_email: string = '';
  profile_pic_uri: string = '';
  roles: Array<any> = [];
  state: string = '';
  status: string = 'deactivated';
  sync_group_assignments: boolean = false;
  tenant_id: number|null = null;
  type: string = 'lms';
  username: string = '';
  zip: string = '';
  courseEnrollments: Array<any> = [];
  learningPathEnrollments: Array<any> = [];

  constructor(data: any = null) {
    super(data);
    const self = this;
    if (data) {
      Object.assign(this, data);
    }
    if (this.status){
      this.status =  this.status.charAt(0).toUpperCase() + this.status.slice(1);
    }
    if (data.groups) {
      this.groups = [];
      data.groups.forEach((data: any) => {
        self.groups.push(new UserGroup(data));
      });
    }
    if (data.roles) {
      this.roles = [];
      data.roles.forEach((data: any) => {
        self.roles.push(new UserRole(data));
      });
    }
    if (data.category) {
      this.category = new UserCategory(data.category);
    }
  }

  name() {
    let name = [];
    if (this.first_name) {
      name.push(this.first_name);
    }
    if (this.middle_name) {
      name.push(this.middle_name);
    }
    if (this.last_name) {
      name.push(this.last_name);
    }
    if (this.name_suffix) {
      name.push(this.name_suffix);
    }
    return name.join(' ');
  }

  address() {
    let address = [];
    if (this.address_1) {
      address.push(this.address_1);
    }
    if (this.address_2) {
      address.push(this.address_2);
    }
    let csz = [];
    if (this.city) {
      csz.push(this.city);
    }
    if (this.state) {
      csz.push(this.state);
    }
    if (this.zip) {
      csz.push(this.zip);
    }
    if (this.country) {
      csz.push(this.country);
    }
    if (csz.length > 0) {
      address.push(csz.join(' '));
    }
    return address.join('<br />');
  }

  formatPhone() {
    if (!this.phone_number) {
      return null;
    }
    let n = this.phone_number.replace(/[^0-9]/g, '');
    if (n.length >= 10) {
      let phone = [];
      let last = n.substr(-4);
      if (last) {
        phone.unshift(last);
      }
      let mid = n.substr(-7, 3);
      if (mid) {
        phone.unshift(mid);
      }
      let first = n.substr(-10, 3);
      if (first) {
        phone.unshift(first);
      }
      let pref = n.substr(0, n.length - 10);
      if (pref) {
        phone.unshift('+' + pref);
      }
      return phone.join('-');
    }
    return null;
  }

  statusIcon(): string {
    switch(this.status) {
      case 'active':
        return 'fa-check';
        break;
      case 'pending':
        return 'fa-circle-info';
        break;
      case 'deactivated':
        return 'fa-ban';
        break;
      default:
        return '';
        break;
    }
  }

  lastLogin(format: string = '') {
    return this.date_format(this.date_last_accessed, format);
  }

  isLoggedIn() {
   if (this.username) {
    return true;
   } else {
    return false;
   }
  }

  hasRole(roleName: string) {
    let hasRole = false;
    this.roles.forEach(role => {
      if (role.title == roleName) {
        hasRole = true;
      }
    });
    return hasRole;
  }

  getCategoryTitle() {
    if (this.category) {
      return this.category.title;
    } else {
      return "None";
    }
  }

  hasPermission(resource: string, action: string) {
    let allowed = false;
    this.roles.forEach((role: any) => {
      if (role.hasPermission(resource, action)) {
        allowed = true;
      }
    });
    return allowed;
  }

  adminLink() {
    return '/admin/users/' + this.id;
  }
}
