<?php
/**
 * Tetra Config
 *
 * An app's decisions about how Tetra behaves, from tetra.json in the app's
 * root, laid over Tetra's defaults from tetra.schema.json.
 *
 *   TetraConfig::get('sessions.expiresDays')
 *
 * Some settings can also be overridden for one site on the Configuration
 * page (the schema marks them x-tetra-site with their config table key, eg.
 * "application.name"). A site's value lives in the config table and wins:
 * Common::configValue() reads the table first and falls back to this. So for
 * those settings the order is: the site's database, then tetra.json, then
 * Tetra's default.
 *
 * PHP version 8.4
 *
 * @category   Helper
 * @package    Tetra
 * @author     Arik Savage <ariksavage@gmail.com>
 * @since      2026-11-13
 */

namespace Core\Config\Helpers;

class TetraConfig
{
  /** The schema, decoded once per request. */
  private static ?object $schema = NULL;

  /** The app's tetra.json, decoded once per request; an empty object without one. */
  private static ?object $app = NULL;

  /** tetra.schema.json, in Tetra's root (beside core/). */
  public static function schemaPath(): string
  {
    return dirname(CORE_ROOT) . '/tetra.schema.json';
  }

  /** tetra.json, in the app's root (beside its core/). */
  public static function appPath(): string
  {
    return SERVER_ROOT . '/tetra.json';
  }

  private static function schema(): object
  {
    if (self::$schema === NULL) {
      $schema = is_file(self::schemaPath()) ? json_decode(file_get_contents(self::schemaPath())) : NULL;
      self::$schema = is_object($schema) ? $schema : (object) ['properties' => new \stdClass()];
    }
    return self::$schema;
  }

  private static function app(): object
  {
    if (self::$app === NULL) {
      $app = is_file(self::appPath()) ? json_decode(file_get_contents(self::appPath())) : NULL;
      self::$app = is_object($app) ? $app : new \stdClass();
    }
    return self::$app;
  }

  /** The schema rule for a setting, or NULL when there's no such setting. */
  public static function rule(string $path): ?object
  {
    $rule = self::schema();
    foreach (explode('.', $path) as $key) {
      if (!isset($rule->properties->$key)) {
        return NULL;
      }
      $rule = $rule->properties->$key;
    }
    return $rule;
  }

  /**
   * A setting's value: the app's tetra.json if it sets it (and the value is
   * one the schema allows), otherwise Tetra's default. NULL for an unknown
   * setting.
   */
  public static function get(string $path)
  {
    $rule = self::rule($path);
    if (!$rule) {
      return NULL;
    }
    $value = self::app();
    foreach (explode('.', $path) as $key) {
      if (!is_object($value) || !property_exists($value, $key)) {
        return $rule->default ?? NULL;
      }
      $value = $value->$key;
    }
    return self::allowed($value, $rule) ? $value : ($rule->default ?? NULL);
  }

  /** Where get() took a setting from: "tetra.json" or "Tetra default". */
  public static function source(string $path): string
  {
    $value = self::app();
    foreach (explode('.', $path) as $key) {
      if (!is_object($value) || !property_exists($value, $key)) {
        return 'Tetra default';
      }
      $value = $value->$key;
    }
    $rule = self::rule($path);
    return $rule && self::allowed($value, $rule) ? 'tetra.json' : 'Tetra default';
  }

  /** Whether a value fits a setting's rule. */
  public static function allowed($value, object $rule): bool
  {
    if (isset($rule->enum)) {
      return in_array($value, $rule->enum, TRUE);
    }
    $type = $rule->type ?? NULL;
    $ok = match ($type) {
      'string' => is_string($value),
      'integer' => is_int($value),
      'number' => is_int($value) || is_float($value),
      'boolean' => is_bool($value),
      'array' => is_array($value) && (!isset($rule->items) || count(array_filter($value, fn($item) => !self::allowed($item, $rule->items))) === 0),
      NULL => TRUE,
      default => FALSE,
    };
    if ($ok && isset($rule->minimum) && is_numeric($value) && $value < $rule->minimum) {
      return FALSE;
    }
    if ($ok && isset($rule->pattern) && is_string($value) && !preg_match('#' . str_replace('#', '\\#', $rule->pattern) . '#u', $value)) {
      return FALSE;
    }
    return $ok;
  }

  /**
   * The settings a site can override, keyed by setting path:
   * ['app.name' => {site: 'application.name', type, key, label, description, rule}, ...]
   */
  public static function siteSettings(): array
  {
    $out = [];
    $walk = function (object $rule, string $path) use (&$walk, &$out) {
      foreach ($rule->properties ?? [] as $key => $child) {
        $childPath = $path === '' ? $key : "{$path}.{$key}";
        if (isset($child->properties)) {
          $walk($child, $childPath);
        } elseif (isset($child->{'x-tetra-site'})) {
          [$type, $siteKey] = explode('.', $child->{'x-tetra-site'}, 2);
          $out[$childPath] = (object) [
            'path' => $childPath,
            'type' => $type,
            'key' => $siteKey,
            'label' => $child->{'x-tetra-label'} ?? $childPath,
            'description' => $child->description ?? '',
            'value_type' => in_array($child->type ?? '', ['integer', 'number'], TRUE) ? 'number' : (($child->type ?? '') === 'boolean' ? 'boolean' : 'text'),
            'rule' => $child,
          ];
        }
      }
    };
    $walk(self::schema(), '');
    return $out;
  }

  /** The overridable setting stored under a config table type and key, or NULL. */
  public static function siteSetting(string $type, string $key): ?object
  {
    foreach (self::siteSettings() as $setting) {
      if ($setting->type === $type && $setting->key === $key) {
        return $setting;
      }
    }
    return NULL;
  }

  /** Forget what was read, eg. after a test changes the files. */
  public static function reset(): void
  {
    self::$schema = NULL;
    self::$app = NULL;
  }
}
