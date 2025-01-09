import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { UserService } from '@tetra/user.service';
import { User } from '@tetra/user';

@Component({
  selector: 'tetra-app-root',
  standalone: true,
  imports: [RouterOutlet],
  templateUrl: './app.component.html',
  styleUrl: './app.component.scss'
})

export class TetraAppComponent {
  title = 'Tetra';
  year = new Date().getFullYear();
  user: User|null = null;

  constructor(
    protected userService: UserService
  ) {
    userService.getUser().subscribe((user: User | null) => {
      if (user) {
        this.user = user;
      }
    });
  }
}
