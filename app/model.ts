import { DateFormat } from '@tetra/date-format';

export class Model {
  id!: number;
  date_created: Date = new Date();
  created_by: number = 0;
  date_modified: Date = new Date();
  modified_by: number = 0;

  constructor(data: any) {
    if (data) {
      this.mapData(data);
      // con
      if (data.date_created) {
        let created: number = parseInt(data.date_created.toString()) * 1000;
        this.date_created = new Date(created);
      }
      if (data.date_modified){
        let modified: number = parseInt(data.date_modified.toString()) * 1000;
        this.date_modified = new Date(modified);
      }
    }
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
