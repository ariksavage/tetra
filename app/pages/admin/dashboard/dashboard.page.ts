import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TetraPage } from '@tetra/page/page.component';


@Component({
		standalone: true,
    selector: "AdminDashboardPage",
    imports: [CommonModule],
    templateUrl: './dashboard.page.html',
    styleUrl: './dashboard.page.scss'
})

export class AdminDashboardPage extends TetraPage {
  override title = 'Admin Dashboard';
  override pageConfig: any = {
    showHeader: true,
    titleInHeader: true,
    showMenu: true,
  };
}
