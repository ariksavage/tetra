import { MessageService } from '../message.service';
import { CommonModule } from '@angular/common';
import { Component, Input, ChangeDetectionStrategy, ChangeDetectorRef } from '@angular/core';


@Component({
	standalone: true,
  selector: '.app-messages',
  imports: [CommonModule],
  templateUrl: './messages.component.html',
  styleUrl: './messages.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush
})

export class MessagesComponent {
  _list: Array<any> = [];
  count: number = 0;
  open: boolean = false;

  constructor(
    private messages: MessageService,
    private cdRef:ChangeDetectorRef
  ) {
    messages.getMessage().subscribe((message: any) => {
      this._list.push(message);
      this.count++;
    })
  }

  ngAfterViewInit() {
    this.cdRef.detectChanges();
  }

  toggle() {
    this.open = !this.open;
  }

  list() {
    return this._list.sort(function(a,b): any{
      return b.date.getTime() - a.date.getTime();
    });
  }

  remove(id: string) {
    this._list = this._list.filter((message: any) => {
      return message.id !== id;
    })
    this.count = this._list.length;
  }

  clearList() {
    this._list = [];
    this.count = 0;
    this.open = false;
  }
}
