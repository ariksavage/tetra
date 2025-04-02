import { MessageService } from '../message.service';
import { CommonModule } from '@angular/common';
import { Component, Input, ChangeDetectionStrategy, ChangeDetectorRef } from '@angular/core';
import {TetraDrawerComponent } from '@tetra/drawer/drawer.component';


@Component({
	standalone: true,
  selector: '.app-messages',
  imports: [ CommonModule, TetraDrawerComponent],
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
      this.cdRef.detectChanges();
    })
  }

  ngAfterViewInit() {
    this.cdRef.detectChanges();
  }

  detectChanges() {
    this.cdRef.detectChanges();
  }

  icon() {
    let icon = 'fa-bell ';
    icon += this.count > 0 ? 'fa-solid' : 'fa-regular';
    return icon;
  }

  list() {
    return this._list.sort(function(a,b): any{
      return b.date.getTime() - a.date.getTime();
    });
  }

  remove(messageId: string) {
    this._list = this._list.filter((message: any) => {
      return message.messageId !== messageId;
    })
    this.count = this._list.length;
  }

  clearList() {
    this._list = [];
    this.count = 0;
    this.open = false;
  }
}
