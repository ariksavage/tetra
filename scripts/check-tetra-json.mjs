#!/usr/bin/env node
/**
 * Check an app's tetra.json against Tetra's schema (tetra.schema.json).
 *
 *   node tetra/scripts/check-tetra-json.mjs [path/to/tetra.json]
 *
 * Defaults to tetra.json in the current folder. No file is fine: the app uses
 * Tetra's defaults. Exits 1, listing each problem, when a setting is unknown,
 * misspelled or has a value the schema doesn't allow, so a deploy stops
 * before copying anything.
 *
 * It knows the parts of JSON Schema that tetra.schema.json uses: objects
 * (with additionalProperties false), enum, type and minimum.
 */
import { readFileSync, existsSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const schema = JSON.parse(readFileSync(join(here, '..', 'tetra.schema.json'), 'utf8'));
const file = resolve(process.argv[2] ?? 'tetra.json');

if (!existsSync(file)) {
  console.log(`No ${file}: the app uses Tetra's defaults.`);
  process.exit(0);
}

let config;
try {
  config = JSON.parse(readFileSync(file, 'utf8'));
} catch (e) {
  console.error(`${file} isn't valid JSON: ${e.message}`);
  process.exit(1);
}

const errors = [];

function typeOf(value) {
  if (Array.isArray(value)) return 'array';
  if (value === null) return 'null';
  if (Number.isInteger(value)) return 'integer';
  return typeof value;
}

function check(value, rule, path) {
  if (rule.enum && !rule.enum.includes(value)) {
    errors.push(`${path}: ${JSON.stringify(value)} isn't one of ${rule.enum.map(v => JSON.stringify(v)).join(', ')}`);
    return;
  }
  if (rule.type) {
    const actual = typeOf(value);
    const ok = rule.type === actual || (rule.type === 'number' && actual === 'integer');
    if (!ok) {
      errors.push(`${path}: expected ${rule.type}, got ${actual}`);
      return;
    }
  }
  if (rule.minimum !== undefined && typeof value === 'number' && value < rule.minimum) {
    errors.push(`${path}: must be at least ${rule.minimum}`);
  }
  if (rule.type === 'object' && rule.properties) {
    for (const [key, child] of Object.entries(value)) {
      const childRule = rule.properties[key];
      if (!childRule) {
        if (rule.additionalProperties === false) {
          errors.push(`${path ? path + '.' : ''}${key}: not a Tetra setting (see tetra.schema.json)`);
        }
        continue;
      }
      check(child, childRule, `${path ? path + '.' : ''}${key}`);
    }
  }
}

check(config, schema, '');

if (errors.length) {
  console.error(`${file} has ${errors.length} problem${errors.length > 1 ? 's' : ''}:`);
  for (const error of errors) {
    console.error(`  - ${error}`);
  }
  process.exit(1);
}
console.log(`${file}: OK`);
