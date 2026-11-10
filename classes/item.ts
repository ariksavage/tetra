import { Model } from '@tetra/classes/model';

export class Item extends Model {
  public name: string = '';
  public category: string = '';
  public price: number  = 0.00;
  public description: string = '';
  public link: string = '';

  constructor(data: any = {}) {
    super(data);
    this.mapData(data);
  }
}
