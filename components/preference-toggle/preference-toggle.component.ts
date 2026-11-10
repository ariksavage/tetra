import { Component, Input, ChangeDetectionStrategy, ChangeDetectorRef } from '@angular/core';
import { PreferencesService } from '@tetra/services/preferences.service';

/**
 * An on / off button for one user preference.
 * The choice is saved through PreferencesService and read back on every page load.
 *
 * Usage: <span class="preference-toggle" key="start_timers_on_click" label="Start timers on click"></span>
 */
@Component({
    selector: '.preference-toggle',
    imports: [],
    templateUrl: './preference-toggle.component.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './preference-toggle.component.scss'
})
export class PreferenceToggleComponent {
  /**
   * Preference name: lowercase letters, numbers and underscores
   */
  @Input() key: string = '';
  @Input() label: string = '';
  /**
   * Longer explanation, shown as a tooltip
   */
  @Input() hint: string = '';
  /**
   * Font Awesome icon classes for each state, eg. "fa-circle-play" / "fa-circle-pause".
   * The icon is what shows on small screens, so the two should look different.
   */
  @Input() iconOn: string = 'fa-toggle-on';
  @Input() iconOff: string = 'fa-toggle-off';
  /**
   * Value to use until the user has chosen
   */
  @Input() default: boolean = false;

  on: boolean = false;

  constructor(
    private preferences: PreferencesService,
    private cdRef: ChangeDetectorRef
  ) {
    this.preferences.all().subscribe(() => this.read());
  }

  ngOnInit() {
    this.read();
  }

  toggle() {
    if (this.key) {
      this.preferences.set(this.key, !this.on);
    }
  }

  private read() {
    const on = !!this.preferences.get(this.key, this.default);
    if (on !== this.on) {
      this.on = on;
      this.cdRef.markForCheck();
    }
  }
}
