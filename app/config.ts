import { Model } from '@tetra/model';

export class Config extends Model {
  public label!: string;
  public type!: string;
  public description!: string;
  public value_type!: string;
  public key!: string;
  public original!: any;
  public value!: any;

  constructor(data: Config) {
    super(data);
    this.original = this.value;
  }

  reset() {
    this.value = this.original;
  }

  touched() {
    return this.value !== this.original;
  }
}
