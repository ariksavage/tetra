import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class CookiesService {

  constructor() { }

  set(cName: string, cValue: any, exDays: number = 30) {
    const d = new Date();
    d.setTime(d.getTime() + (exDays*24*60*60*1000));
    let expires = "expires="+ d.toUTCString();
    document.cookie = cName + "=" + cValue + ";" + expires + ";path=/";
  }

  read(cName: string) {
    let name = cName + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for(let i = 0; i <ca.length; i++) {
      let c = ca[i];
      while (c.charAt(0) == ' ') {
        c = c.substring(1);
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length);
      }
    }
    return "";
  }

  delete(cName: string) {
    this.set(cName, null, -100);
  }
}
