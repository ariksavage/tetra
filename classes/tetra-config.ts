import { InjectionToken, Provider } from '@angular/core';
import schema from '../tetra.schema.json';

/**
 * Tetra Config: an app's decisions about how Tetra behaves, from tetra.json in
 * the app's root (see tetra.schema.json for every setting and its default).
 *
 * In the app config:
 *
 *   import tetraJson from '../../tetra.json';
 *   providers: [provideTetraConfig(tetraJson), ...]
 *
 * Then anywhere: `inject(TETRA_CONFIG).siteMenu.collapse`.
 *
 * Settings a site can override on the Configuration page (the name, session
 * length) reach the page through the app config API instead, already merged
 * with that site's values; read those from AppService.getConfig().
 */
export interface TetraConfig {
  app: {
    name: string;
    copyright: { before: string; after: string };
  };
  sessions: {
    expiresDays: number;
    passwordResetExpiresDays: number;
  };
  siteMenu: {
    collapse: 'auto' | 'always' | 'never';
  };
  header: {
    longTitle: 'wrap' | 'truncate';
  };
}

/** A schema rule: the parts of JSON Schema that tetra.schema.json uses. */
interface Rule {
  type?: string;
  enum?: unknown[];
  minimum?: number;
  default?: unknown;
  properties?: Record<string, Rule>;
  additionalProperties?: boolean;
}

const SCHEMA = schema as unknown as Rule;

/** Every setting at its default, read from the schema. */
export function tetraDefaults(rule: Rule = SCHEMA): any {
  if (rule.properties) {
    const out: any = {};
    for (const [key, child] of Object.entries(rule.properties)) {
      if (key === '$schema') {
        continue;
      }
      const value = tetraDefaults(child);
      if (value !== undefined) {
        out[key] = value;
      }
    }
    return out;
  }
  return rule.default;
}

function typeOf(value: unknown): string {
  if (Array.isArray(value)) return 'array';
  if (value === null) return 'null';
  if (Number.isInteger(value)) return 'integer';
  return typeof value;
}

/**
 * What's wrong with a tetra.json, if anything: unknown settings and values the
 * schema doesn't allow. tetra/scripts/check-tetra-json.mjs runs the same check
 * before a deploy.
 */
export function validateTetraConfig(value: unknown, rule: Rule = SCHEMA, path = ''): string[] {
  const errors: string[] = [];
  if (rule.enum && !rule.enum.includes(value)) {
    return [`${path}: ${JSON.stringify(value)} isn't one of ${rule.enum.map(v => JSON.stringify(v)).join(', ')}`];
  }
  if (rule.type) {
    const actual = typeOf(value);
    if (!(rule.type === actual || (rule.type === 'number' && actual === 'integer'))) {
      return [`${path}: expected ${rule.type}, got ${actual}`];
    }
  }
  if (rule.minimum !== undefined && typeof value === 'number' && value < rule.minimum) {
    errors.push(`${path}: must be at least ${rule.minimum}`);
  }
  if (rule.type === 'object' && rule.properties && value && typeof value === 'object') {
    for (const [key, child] of Object.entries(value as Record<string, unknown>)) {
      const childPath = path ? `${path}.${key}` : key;
      const childRule = rule.properties[key];
      if (!childRule) {
        if (rule.additionalProperties === false) {
          errors.push(`${childPath}: not a Tetra setting (see tetra.schema.json)`);
        }
        continue;
      }
      errors.push(...validateTetraConfig(child, childRule, childPath));
    }
  }
  return errors;
}

/** The app's settings laid over the defaults; an invalid setting keeps its default. */
function merge(defaults: any, overrides: any, rule: Rule = SCHEMA): any {
  if (!overrides || typeof overrides !== 'object' || !rule.properties) {
    return defaults;
  }
  const out: any = { ...defaults };
  for (const [key, value] of Object.entries(overrides)) {
    const childRule = rule.properties[key];
    if (!childRule || key === '$schema') {
      continue;
    }
    if (childRule.properties) {
      out[key] = merge(defaults?.[key], value, childRule);
    } else if (!validateTetraConfig(value, childRule, key).length) {
      out[key] = value;
    }
  }
  return out;
}

/** The app's Tetra Config. Tetra's defaults until the app provides its tetra.json. */
export const TETRA_CONFIG = new InjectionToken<TetraConfig>('TETRA_CONFIG', {
  factory: () => tetraDefaults() as TetraConfig
});

/**
 * Provide the app's tetra.json. Problems are logged and that setting keeps
 * its default, so a mistake never stops the app; the deploy's check stops it
 * from going live.
 */
export function provideTetraConfig(tetraJson: unknown): Provider {
  const errors = validateTetraConfig(tetraJson);
  if (errors.length) {
    console.error('tetra.json:\n  ' + errors.join('\n  '));
  }
  return { provide: TETRA_CONFIG, useValue: merge(tetraDefaults(), tetraJson) as TetraConfig };
}
