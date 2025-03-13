export class Message {
  public text: string = '';
  public type: string = '';
  public id: string = '';
  public date: Date = new Date();

  constructor(text: string, type: string = '') {
    this.text = text;
    this.type = type;
    this.id = this.makeId(16);
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

  since() {
    let str = '';
    let n = 0;
    const msSecond = 1000;
    const msMinute = 60 * msSecond;
    const msHour = 60 * msMinute;
    const msDay = 24 * msHour;
    const msMonth = 30 * msDay;
    const msYear = 365 * msDay;

    const elapsed = new Date().getTime() - this.date.getTime();

    if (elapsed < msMinute) {
      n = Math.round(elapsed/1000);
      str = n + ' second';
      str += n == 1 ? ' ago' : 's ago';
    } else if (elapsed < msHour) {
      // return Math.round(elapsed/msMinute) + ' minutes ago';
      n = Math.round(elapsed/msMinute);
      str = n + ' minute';
      str += n == 1 ? ' ago' : 's ago';
    } else if (elapsed < msDay ) {
      // return Math.round(elapsed/msHour ) + ' hours ago';
      n = Math.round(elapsed/msHour);
      str = n + ' hour';
      str += n == 1 ? ' ago' : 's ago';
    } else if (elapsed < msMonth) {
      // return 'approximately ' + Math.round(elapsed/msDay) + ' days ago';
      n = Math.round(elapsed/msDay);
      str = 'approximately' + n + ' day';
      str += n == 1 ? ' ago' : 's ago';
    } else if (elapsed < msYear) {
      // return 'approximately ' + Math.round(elapsed/msMonth) + ' months ago';
      n = Math.round(elapsed/msMonth);
      str = 'approximately' + n + ' month';
      str += n == 1 ? ' ago' : 's ago';
    } else {
      // return 'approximately ' + Math.round(elapsed/msYear ) + ' years ago';
      n = Math.round(elapsed/msYear);
      str = 'approximately' + n + ' year';
      str += n == 1 ? ' ago' : 's ago';
    }

    return str;
  }
}
