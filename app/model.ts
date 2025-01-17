import { DateFormat } from '@tetra/date-format';

export class Model {
  id!: number;
  date_created!: Date;
  created_by!: number;
  date_modified!: Date;
  modified_by!: number;

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
    const date = new DateFormat(format, dateStr);
    return date.string();
  }
  protected mapData(data: any) {
    Object.assign(this, data);
  }
}
