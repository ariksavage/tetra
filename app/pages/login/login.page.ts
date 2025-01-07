import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TetraPage } from '@tetra/page/page.component';

@Component({
  standalone: true,
  imports: [ CommonModule ],
  templateUrl: './login.page.html',
  styleUrl: './login.page.scss',
})

export class LoginPage extends TetraPage {
  override title = 'Login';
}
