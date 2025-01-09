export class Model {
  id: number|null = null;
  date_created: Date = new Date();
  created_by: number|null = null;
  date_modified: Date = new Date();
  modified_by: number|null = null;

  constructor(data: any = null) {
    const self = this;
    if (data) {
      if (data.id) {
        self.id = data.id;
        delete data.id;
      }
      if (data.date_created) {
        self.date_created = new Date(data.date_created);
        delete data.date_created;
      }
      if (data.created_by) {
        self.created_by = data.created_by;
        delete data.created_by;
      }
      if (data.date_modified) {
        self.date_modified = new Date(data.date_modified);
        delete data.date_modified;
      }
      if (data.modified_by) {
        self.modified_by = data.modified_by;
        delete data.modified_by;
      }
    this.mapData(data);
    }


  }
  protected mapData(data: any){
    Object.assign(this, data);
  }
}
