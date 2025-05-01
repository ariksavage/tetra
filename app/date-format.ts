export class DateFormat {
  dateStr: string = '';
  format: string = 'YYYY-M-d'
  date: Date;

  constructor(format: string, dateStr: any = '') {
    this.format = format;
    this.dateStr = dateStr;
    if (this.dateStr){
      this.date = new Date(this.dateStr);
    } else {
      this.date = new Date();
    }
  }

  shortDay(i: number) {
    const days = [
      'Sun',
      'Mon',
      'Tue',
      'Wed',
      'Thu',
      'Fri',
      'Sat'
    ];
    return days[i];
  }

  day(i: number) {
    const days = [
      'Sunday',
      'Monday',
      'Tuesday',
      'Wednesday',
      'Thursday',
      'Friday',
      'Saturday'
    ];
    return days[i];
  }

  shortMonth(i: number) {
    const months = [
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'May',
      'Jun',
      'Jul',
      'Aug',
      'Sep',
      'Oct',
      'Nov',
      'Dec'
    ];
    return months[i];
  }

  month(i: number) {
    const months = [
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December'
    ];
    return months[i];
  }

  getOrdinal(i: number) {
    let S = '';
    switch(i) {
      case 1:
      case 21:
      case 31:
        S = 'st';
        break;
      case 2:
      case 22:
        S = 'nd';
        break;
      case 3:
      case 23:
        S = 'rd';
        break;
      default:
        S = 'th';
        break;
    }
    return S;
  }

  str(n: number, leadingZero: boolean = false) {
    let str = n.toString();
    if (leadingZero){
      str = str.padStart(2, '0');
    }
    return str;
  }

  string() {
    let str = '';
    for (var i = 0; i < this.format.length; i++) {
      const char = this.format.charAt(i);
      switch(char) {
        case 'd': // The day of the month (from 01 to 31)
          const d = this.str(this.date.getDate(), true);
          str += d;
          break;
        case 'D': // A textual representation of a day (three letters)
          const D = this.shortDay(this.date.getDay());
          str += D;
          break;
        case 'j': // The day of the month without leading zeros (1 to 31)
          const j = this.str(this.date.getDate());
          str += j;
          break;
        case 'l': // A full textual representation of a day
          const l = this.day(this.date.getDay());
          str += l;
          break;
        case 'N': // The ISO-8601 numeric representation of a day (1 for Monday, 7 for Sunday)
          const N = this.str(this.date.getDay());
          str += N;
          break;
        case 'S': // The English ordinal suffix for the day of the month (2 characters st, nd, rd or th. Works well with j)
          const S = this.getOrdinal(this.date.getDate());
          str += S;
          break;
        case 'w': // A numeric representation of the day (0 for Sunday, 6 for Saturday)
          const w = this.str(this.date.getDay());
          str += w;
          break;
        case 'F': // A full textual representation of a month (January through December)
          const F = this.month(this.date.getMonth());
          str += F;
          break;
        case 'm': // A numeric representation of a month (from 01 to 12)
          const m = this.str(this.date.getMonth() + 1, true);
          str += m;
          break;
        case 'M': // A short textual representation of a month (three letters)
          const M = this.shortMonth(this.date.getMonth());
          str += M;
          break;
        case 'n': // A numeric representation of a month, without leading zeros (1 to 12)
          const n = this.str(this.date.getMonth() + 1);
          str += n;
          break;
        case 'Y': // A four digit representation of a year
          const Y = this.str(this.date.getFullYear());
          str += Y;
          break;
        case 'y': // A two digit representation of a year
          const y = this.str(this.date.getFullYear()).slice(-2);
          str += y;
          break;
        case 'G': // 24-hour format of an hour (0 to 23)
          const G = this.str(this.date.getHours());
          str += G;
          break;
        case 'H': // 24-hour format of an hour (00 to 23)
          const H = this.str(this.date.getHours(), true);
          str += H;
          break;
        case 'g': // 12-hour format of an hour (1 to 12)
          const g = (this.date.getHours() > 12) ? this.str(this.date.getHours() - 12) : this.str(this.date.getHours());
          str += g;
          break;
        case 'h': // 12-hour format of an hour (01 to 12)
          const h = (this.date.getHours() > 12) ? this.str(this.date.getHours() - 12, true) : this.str(this.date.getHours(), true);
          str += h;
          break;
        case 'a': // Lowercase am or pm
          const a = this.date.getHours() > 12 ? 'pm' : 'am';
          str += a;
          break;
        case 'A': // Uppercase AM or PM
          const A = this.date.getHours() > 12 ? 'PM' : 'AM';
          str += A;
          break;
        case 'b': // Lowercase a or p
          const b = this.date.getHours() > 12 ? 'p' : 'a';
          str += b;
          break;
        case 'B': // Uppercase A or P
          const B = this.date.getHours() > 12 ? 'P' : 'A';
          str += B;
          break;
        case 'i': // Minutes with leading zeros (00 to 59)
          const i = this.str(this.date.getMinutes(), true);
          str += i;
          break;
        case 's': // Seconds, with leading zeros (00 to 59)
          const s = this.str(this.date.getMinutes(), true);
          str += s;
          break;
        case 'r': // relative difference between the date and now
          str += this.relativeDate();
          break;
        default:
          str += char;
          break;
      }
    }
    return str;
  }

  relativeDate() {
    let str = '';
    const now = new Date();
    let diff = now.valueOf() - this.date.valueOf();
    let seconds = Math.floor(diff / 1000);
    if (seconds < 60) {
      str += `${seconds} second`
      if (seconds > 1){
        str += 's';
      }
    } else {
      let minutes = Math.floor(seconds / 60);
      if (minutes < 60) {
        str += `${minutes} minute`
        if (minutes > 1) {
          str += 's';
        }
      } else {
        let hours = Math.floor(minutes / 60);
        if (hours < 24) {
          str += `${hours} hour`
          if (hours > 1){
            str += 's';
          }
        } else {
          let days = Math.floor(hours / 24);
          if (days < 365) {
            str += `${days} day`
            if (days > 1){
              str += 's';
            }
          } else {
            let years = Math.floor(days / 365);
            str += `${years} year`
            if (years > 1){
              str += 's';
            }
          }
        }
      }
    }
    str += ' ago';
    return str;
  }
}
