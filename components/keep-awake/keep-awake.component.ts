import { Component, ChangeDetectionStrategy, ChangeDetectorRef } from '@angular/core';
import { PreferencesService } from '@tetra/services/preferences.service';
import { WakeLockService } from '@tetra/services/wake-lock.service';

/**
 * "Keep screen on" toggle.
 * The choice is saved as the keep_screen_on preference and applied on every page load.
 */
@Component({
    selector: '.keep-awake',
    imports: [],
    templateUrl: './keep-awake.component.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './keep-awake.component.scss'
})
export class KeepAwakeComponent {
  static readonly preference = 'keep_screen_on';

  supported: boolean = false;
  on: boolean = false;

  constructor(
    private preferences: PreferencesService,
    private wakeLock: WakeLockService,
    private cdRef: ChangeDetectorRef
  ) {
    this.supported = this.wakeLock.supported();
    this.preferences.all().subscribe(() => {
      const on = !!this.preferences.get(KeepAwakeComponent.preference, false);
      if (on !== this.on) {
        this.on = on;
        this.apply();
        this.cdRef.markForCheck();
      }
    });
  }

  toggle() {
    this.preferences.set(KeepAwakeComponent.preference, !this.on);
  }

  private apply() {
    if (!this.supported) {
      return;
    }
    if (this.on) {
      this.wakeLock.enable();
    } else {
      this.wakeLock.disable();
    }
  }
}
