import { Component, ChangeDetectionStrategy } from '@angular/core';

import { Page } from '@tetra/pages/page/page.component';
import { Config } from '@tetra/classes/config';
import { FieldTextComponent } from '@tetra/components/field-text/field-text.component';
import { FieldTextAreaComponent } from '@tetra/components/field-textarea/field-textarea.component';
import { FieldNumberComponent } from '@tetra/components/field-number/field-number.component';
import { FieldCheckboxComponent } from '@tetra/components/field-checkbox/field-checkbox.component';
import { AppLayoutComponent } from '@components/app-layout/app-layout.component';

/**
 * Field used to edit each config value_type.
 * Unknown value types fall back to a text field.
 */
export const FIELD_FOR_TYPE: { [valueType: string]: string } = {
  text: 'text',
  longtext: 'textarea',
  number: 'number',
  boolean: 'checkbox',
  object: 'json'
};

export interface ConfigGroup {
  type: string;
  items: Config[];
}

@Component({
    selector: 'config-page',
    imports: [AppLayoutComponent, FieldTextComponent, FieldTextAreaComponent, FieldNumberComponent, FieldCheckboxComponent],
    templateUrl: './config.page.html',
    changeDetection: ChangeDetectionStrategy.Eager,
    styleUrl: './config.page.scss'
})
export class ConfigPageComponent extends Page {
  public override title = 'Configuration';
  config: Config[] = [];
  groups: ConfigGroup[] = [];
  saving: boolean = false;

  override onLoad() {
    this.ready = true;
    this.fetch();
  }

  fetch() {
    return this.core.get('config', 'index').then((data: any) => {
      if (data && data.config) {
        this.setConfig(data.config);
      }
    });
  }

  /**
   * Build Config items from API rows and group them by type.
   * Object values are edited as JSON text.
   */
  setConfig(rows: any[]) {
    this.config = rows.map((row: any) => {
      if (this.fieldFor(row.value_type) === 'json') {
        row = { ...row, value: JSON.stringify(row.value ?? null, null, 2) };
      }
      return new Config(row);
    });
    const groups: { [type: string]: ConfigGroup } = {};
    this.config.forEach((item) => {
      const type = item.type || 'application';
      if (!groups[type]) {
        groups[type] = { type, items: [] };
      }
      groups[type].items.push(item);
    });
    this.groups = Object.values(groups);
  }

  fieldFor(valueType: string): string {
    return FIELD_FOR_TYPE[valueType] || 'text';
  }

  groupTitle(type: string): string {
    return type.replace(/[_-]+/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
  }

  isValid(item: Config): boolean {
    switch (this.fieldFor(item.value_type)) {
      case 'number':
        return item.value !== '' && item.value !== null && !isNaN(Number(item.value));
      case 'json':
        try {
          JSON.parse(item.value);
          return true;
        } catch (e) {
          return false;
        }
      default:
        return true;
    }
  }

  /** Validator for the JSON textarea field */
  validateJson = (value: string) => {
    try {
      JSON.parse(value);
      return true;
    } catch (e) {
      return false;
    }
  };

  changed(): Config[] {
    return this.config.filter((item) => item.touched());
  }

  canSave(): boolean {
    const changed = this.changed();
    return !this.saving && changed.length > 0 && changed.every((item) => this.isValid(item));
  }

  /**
   * Payload containing only changed values.
   * JSON text is parsed back into objects.
   */
  savePayload() {
    return {
      config: this.changed().map((item) => ({
        id: item.id,
        value: this.fieldFor(item.value_type) === 'json' ? JSON.parse(item.value) : item.value
      }))
    };
  }

  save() {
    if (!this.canSave()) {
      return;
    }
    this.saving = true;
    return this.core.post('config', 'index', this.savePayload()).then((data: any) => {
      this.saving = false;
      if (data && data.config) {
        this.setConfig(data.config);
        this.app.fetchAppConfig();
      }
    });
  }

  resetAll() {
    this.config.forEach((item) => item.reset());
  }
}
