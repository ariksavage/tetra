import { Component } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { Title } from "@angular/platform-browser";

import { CoreService } from '@tetra/core.service';
import { AppService } from '@tetra/app.service';

@Component({
  selector: 'app-page',
  standalone: true,
  imports: [],
  templateUrl: './page.component.html',
  styleUrl: './page.component.scss'
})
export class TetraPage {
  public title: string = 'Page';

  constructor(
    protected core: CoreService,
    protected app: AppService,
    protected route: Router,
    protected activeRoute: ActivatedRoute,
    protected titleService:Title,
  ) {}

  ngOnInit() {
    const self = this;
    self.load();
  }

  load() {
    this.titleService.setTitle(this.title);
    return this.onLoad();
  }

  onLoad() {
    const self = this;
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        resolve(true);
      }, 10);
    });
  }
}
