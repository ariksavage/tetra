import { Component, Input, ChangeDetectionStrategy, ChangeDetectorRef } from '@angular/core';
import { ConsentService } from '@tetra/services/consent.service';

@Component({
    selector: '.cookie-consent',
    imports: [],
    templateUrl: './cookie-consent.component.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './cookie-consent.component.scss'
})
export class CookieConsentComponent {
  /**
   * What the optional cookies are used for
   */
  @Input() message: string = 'This site would like to store optional cookies to remember your preferences.';

  open: boolean = false;

  constructor(
    private consent: ConsentService,
    private cdRef: ChangeDetectorRef
  ) {
    this.consent.isOpen().subscribe((open: boolean) => {
      this.open = open;
      this.cdRef.markForCheck();
    });
  }

  accept() {
    this.consent.accept();
  }

  decline() {
    this.consent.decline();
  }
}
