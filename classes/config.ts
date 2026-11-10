import { Model } from '@tetra/classes/model';

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
    if (typeof data.label !== 'undefined') {
      this.label = data.label;
    }
    if (typeof data.type !== 'undefined') {
      this.type = data.type;
    }
    if (typeof data.description !== 'undefined') {
      this.description = data.description;
    }
    if (typeof data.key !== 'undefined') {
      this.key = data.key;
    }
    if (typeof data.value !== 'undefined') {
      this.value = data.value;
    }
    if (typeof data.value_type !== 'undefined') {
      this.value_type = data.value_type;
    }
    this.original = this.value;
  }

  reset() {
    this.value = this.original;
  }

  touched() {
    return this.value !== this.original;
  }
}
