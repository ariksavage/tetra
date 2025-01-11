export class Model {
  id!: number;
  date_created!: Date;
  created_by!: number;
  date_modified!: Date;
  modified_by!: number;

  constructor(data: Model) {

    // const self = this;
    if (data) {
      Object.assign(this, data);
      //   self.id = data.id;
      //   self.date_created = new Date(data.date_created);
      //   self.created_by = data.created_by;
      //   self.date_modified = new Date(data.date_modified);
      //   self.modified_by = data.modified_by;
      // this.mapData(data);
    // }
      this.date_created = new Date(data.date_created);
      this.date_modified = new Date(data.date_modified);
    }
    this.afterConstruct();

  }
  protected mapData(data: any) {
    Object.assign(this, data);
  }


  protected afterConstruct() {

  }
}
