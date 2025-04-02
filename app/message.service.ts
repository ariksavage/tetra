import { Injectable } from '@angular/core';
import { Observable, Subject, BehaviorSubject} from 'rxjs';
import { Message } from '@tetra/message';

@Injectable({
  providedIn: 'root'
})
export class MessageService {
  last = new Subject<Message>();
  _last?: Message;


  setMessage(message: Message) {
    this._last = message;
    this.last.next(message);
  }

  getMessage() {
    return this.last.asObservable();
  }

  /**
   * Create a new message
   * @var String text Message text
   * @var String type Message type, eg error
   * @var Number lifespan Time in milliseconds for the message to be displayed.
   * @return String message ID.
   */
  add(text: string, type: string = '') {
    const message = new Message(text, type);
    this.setMessage(message);
    return message.messageId;
  }

  /**
   * Set an error message for the user
   * @var String error Error message text
   * @var Number lifespan Time in milliseconds for the message to be displayed.
   * @return String message ID.
   */
  error(error: string, key: string = '', lifespan: number = 60000): string {
    return this.add(error, 'error');
  }
}
