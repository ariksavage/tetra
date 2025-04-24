import { DateFormat } from '@tetra/date-format';

export class Model {
  public id!: number;
  public date_created!: Date;
  public created_by!: number;
  public date_modified!: Date;
  public modified_by!: number;

  constructor(data: any) {
    if (data) {
      data = this.parseDatesDefault(data);
      data = this.parseDates(data);
      this.mapData(data);
    }
  }

  protected parseDatesDefault(data: any) {
    if (data.date_created) {
      this.date_created = this.parseDate(data.date_created);
      delete data.date_created;
    }
    if (data.date_modified) {
      this.date_modified = this.parseDate(data.date_modified);
      delete data.date_modified;
    }
    return data;
  }

  protected parseDates(data: any) {
    return data;
  }

  protected parseDate(value: number) {
    var d =0;
    if (value) {
      d = value * 1000;
    }
    return new Date(d);
  }

  protected toTitleCase(str: string) {
    return str.replace(
      /\w\S*/g,
      function(txt) {
        return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
      }
    );
  }

  protected publicUri(uri: string){
    let updated = '';
    if (uri) {
      updated = '/public/' + uri.split('/public/')[1];
    }
    return updated;
  }

  protected formatDate(format: string, dateStr: string) {
    if (!dateStr) {
      return '';
    }
    const date = new DateFormat(format, dateStr);
    return date.string();
  }
  protected mapData(data: any) {
    Object.assign(this, data);
  }

  public dateCreated() {
    return this.formatDate('M j, Y', this.date_created.toString());
  }

  public sinceCreated() {
    return this.since(this.date_created).replace('approximately','').trim();
  }

  public dateModified() {
    return this.formatDate('M j, Y', this.date_modified.toString());
  }

  public sinceModified() {
    return this.since(this.date_modified).replace('approximately','').trim();
  }

  public since(date: Date) {
    if (!date) {
      return '';
    }
    let str = '';
    let n = 0;
    const msSecond = 1000;
    const msMinute = 60 * msSecond;
    const msHour = 60 * msMinute;
    const msDay = 24 * msHour;
    const msMonth = 30 * msDay;
    const msYear = 365 * msDay;

    const elapsed = new Date().getTime() - date.getTime();

    if (elapsed < msMinute) {
      n = Math.round(elapsed / msSecond);
      str = n + ' second';
    } else if (elapsed < msHour) {
      n = Math.round(elapsed / msMinute);
      str = n + ' minute';
    } else if (elapsed < msDay ) {
      n = Math.round(elapsed / msHour);
      str = n + ' hour';
    } else if (elapsed < msMonth) {
      n = Math.round(elapsed / msDay);
      str = 'approximately ' + n + ' day';
    } else if (elapsed < msYear) {
      n = Math.round(elapsed / msMonth);
      str = 'approximately ' + n + ' month';
    } else {
      n = Math.round(elapsed / msYear);
      str = 'approximately ' + n + ' year';
    }
    str += n == 1 ? ' ago' : 's ago';

    return str;
  }

  preSave() {
    let copy = JSON.parse(JSON.stringify(this));
    return copy;
  }
}
