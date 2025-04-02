import { Model } from '@tetra/model';

export class Message extends Model{
  public text: string = '';
  public type: string = '';
  public messageId: string = '';
  public date: Date = new Date();

  constructor(text: string, type: string = '') {
    super({});
    this.text = text;
    this.type = type;
    this.messageId = this.makeId(16);
  }

  /**
   * Create a random string as an ID
   * @param number length Length of the random string to generate
   */
  makeId(length: number): string {
    let result = '';
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const charactersLength = characters.length;

    for (let i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() * charactersLength));
    }

    return result;
  }

  dateStr() {
    let hours = this.date.getHours();
    let a = hours > 12 ? 'pm' : 'am';
    hours = hours > 12 ? hours - 12 : hours;
    let h = hours.toString().padStart(2, '0');
    let i = this.date.getMinutes().toString().padStart(2, '0');
    let s = this.date.getSeconds().toString().padStart(2, '0');
    return `${h}:${i}:${s}${a}`;
  }

  messageAge() {
    return this.since(this.date);
  }
}
