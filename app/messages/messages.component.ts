import { ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MessageService } from '../message.service';
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
  toastMessage: any = null;
  toastClass: string = 'up';
  @ViewChild('messagesDrawer') drawer: TetraDrawerComponent = {} as TetraDrawerComponent;

  test() {
    this.toastClass = this.toastClass == 'up' ? 'down' : 'up';
  }

  constructor(
    private messages: MessageService,
    private cdRef:ChangeDetectorRef
  ) {
    messages.getMessage().subscribe((message: any) => {
      this.handleToast(message);
      this._list.push(message);
      this.count++;
      this.cdRef.detectChanges();

    })
  }
  dismissToast(id: string) {
    this.remove(id);
    this.toastMessage = null;
    this.toastClass = 'up';
  }

  handleToast(message: any) {
    this.toastMessage = message;
    this.cdRef.detectChanges();
    setTimeout(() => {
      this.toastClass = 'transition';
      this.cdRef.detectChanges();
      setTimeout(() => {
        this.toastClass = 'down';
        this.cdRef.detectChanges();
        setTimeout(() => {
          this.toastClass = 'right';
          this.cdRef.detectChanges();
          setTimeout(() => {
            this.toastMessage = null;
            this.toastClass = 'up';
            this.cdRef.detectChanges();
          }, 1000);
        }, 6000);
      }, 100);
    }, 10);
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
    if (this.count == 0) {
      this.drawer.toggle();
      this.cdRef.detectChanges();
    }
  }

  clearList() {
    this._list = [];
    this.count = 0;
    this.open = false;
  }
}
