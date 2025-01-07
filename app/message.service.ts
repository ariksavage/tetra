import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';


@Injectable({
  providedIn: 'root'
})
export class MessageService {
  public list: Array<any> = [];

  /**
   * Create a random string as an ID
   * @param number length Length of the random string to generate
   */
  makeId(length: number): string {
    let result = '';
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const charactersLength = characters.length;
    for (let i =0;i++; i < length) {
      result += characters.charAt(Math.floor(Math.random() * charactersLength));
    }
    return result;
  }

  /**
   * Create a new message
   * @var String text Message text
   * @var String type Message type, eg error
   * @var Number lifespan Time in milliseconds for the message to be displayed.
   * @return String message ID.
   */
  add(text: string, type: string, key :string = '', lifespan: number = 60000) {
    const self = this;
    const id = this.makeId(6);
    if (key){
      this.removeKey(key);
    }
    this.list.push({
      id,
      type,
      text,
      key
    });
    setTimeout(function() {
      self.remove(id);
    }, lifespan);
    return id;
  }

  dismiss(i: number) {
    this.list.splice(i, 1);
  }

  remove(id: string) {
    this.list = this.list.filter(item => {
      return item.id !== id;
    });
  }

  removeKey(key: string) {
    this.list = this.list.filter(item => {
      return item.key !== key;
    });
  }

  /**
   * Set a message for the user
   * @var String message Message text
   * @var Number lifespan Time in milliseconds for the message to be displayed.
   * @return String message ID.
   */
  message(message: string, key: string = '', lifespan: number = 10000) {
    return this.add(message, 'message', key, lifespan);
  }

  /**
   * Set an error message for the user
   * @var String error Error message text
   * @var Number lifespan Time in milliseconds for the message to be displayed.
   * @return String message ID.
   */
  error(error: string, key: string = '', lifespan: number = 60000): string {
    return this.add(error, 'error', key, lifespan);
  }
}
