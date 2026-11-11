import { ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Component, Input, ChangeDetectionStrategy, ChangeDetectorRef } from '@angular/core';

import { MessageService } from '@tetra/services/message.service';


@Component({
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
  toggleOpen: boolean = false;
  toastMessage: any = null;

  toggle() {
    this.toggleOpen = !this.toggleOpen;
  }

  show() {
    this.open = true;
  }

  hide() {
    this.open = false;
  }

  hideAll() {
    this.open = false;
    this.toggleOpen = false;
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
  /**
   * How long a toast stays up, in milliseconds. Errors stay until dismissed,
   * and any toast stays while the pointer or keyboard focus is on it.
   */
  toastDuration = 5000;
  private toastTimer?: ReturnType<typeof setTimeout>;

  dismissToast(id: string) {
    this.remove(id);
    this.clearToast();
  }

  handleToast(message: any) {
    this.toastMessage = message;
    this.cdRef.detectChanges();
    this.resumeToast();
  }

  /** Hold the toast while it's hovered or focused. */
  pauseToast() {
    clearTimeout(this.toastTimer);
  }

  /** Start (or restart) the countdown to hiding the toast, unless it's an error. */
  resumeToast() {
    clearTimeout(this.toastTimer);
    if (!this.toastMessage || this.toastMessage.type === 'error') {
      return;
    }
    this.toastTimer = setTimeout(() => this.clearToast(), this.toastDuration);
  }

  private clearToast() {
    clearTimeout(this.toastTimer);
    this.toastMessage = null;
    this.cdRef.detectChanges();
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
      this.cdRef.detectChanges();
    }
  }

  clearList() {
    this._list = [];
    this.count = 0;
    this.open = false;
  }
}
