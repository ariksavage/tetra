<?php
/**
 * Config API
 *
 * View and edit application configuration.
 *
 * PHP version 8.4
 *
 * @category   API
 * @package    Core
 * @author     Arik Savage <ariksavage@gmail.com>
 * @version    2.0
 * @since      2026-01-06
 */
namespace Core\Config\API;

use \Core\Config\Models\Config as ConfigItem;
use \Core\Config\Helpers\TetraConfig;

/**
 * Class Config
 *
 * Lists and updates rows in the config table.
 *
 * Access is controlled by the `config` permission dimension
 * (granted to admin, and inherited by root).
 *
 * Two kinds of setting:
 *
 * - The app's own options (eg. Work's billing settings): rows added by
 *   migrations, edited here. Rows are never created or deleted for these.
 * - Tetra settings a site may override (tetra.schema.json marks them
 *   x-tetra-site: the name, session length...). Their value comes from the
 *   app's tetra.json or Tetra's default until this site sets its own, which
 *   saves a row; "use app default" deletes it again.
 *
 * GET    /core/config/index                 {config: [rows], settings: [Tetra settings]}
 * POST   /core/config/index                 {config: [{id, value}]}
 * POST   /core/config/setting               {path, value} This site's value for a Tetra setting
 * DELETE /core/config/setting/{path}        Back to the app's value (tetra.json or Tetra's default)
 * GET    /core/config/robots                The site's robots.txt as crawlers get it, with its layers
 */
class Config extends \Core\Base\API\Base {

  public function __construct()
  {
    parent::__construct('config', '\Core\Config\Models\Config', 'Config', 'Config');
  }

  /**
   * Load all config rows, ordered for display.
   *
   * @return array Array of Config models
   */
  protected function allConfig(): array
  {
    $rows = $this->select()
      ->from('config')
      ->orderBy('type')
      ->orderBy('label')
      ->execute(FALSE, '\Core\Config\Models\Config') ?: [];
    // Tetra's own settings are listed under settings() instead.
    return array_values(array_filter($rows, fn($row) => !TetraConfig::siteSetting($row->type, $row->key)));
  }

  /**
   * The Tetra settings a site may override: the value in use, where it came
   * from, and the app's value (what "use app default" goes back to).
   */
  protected function settings(): array
  {
    $out = [];
    foreach (TetraConfig::siteSettings() as $setting) {
      $row = $this->siteRow($setting);
      $appValue = TetraConfig::get($setting->path);
      $out[] = (object) [
        'path' => $setting->path,
        'label' => $setting->label,
        'description' => $setting->description,
        'value_type' => $setting->value_type,
        'options' => $setting->rule->enum ?? NULL,
        'minimum' => $setting->rule->minimum ?? NULL,
        'value' => $row ? $row->value : $appValue,
        'source' => $row ? 'site' : (TetraConfig::source($setting->path) === 'tetra.json' ? 'tetra.json' : 'tetra'),
        'app_value' => $appValue,
        'app_source' => TetraConfig::source($setting->path) === 'tetra.json' ? 'tetra.json' : 'tetra',
      ];
    }
    return $out;
  }

  /** This site's row for a Tetra setting, or NULL while it uses the app's value. */
  protected function siteRow(object $setting): ?ConfigItem
  {
    $row = new ConfigItem();
    $found = $this->select()->from('config')->where('type', '=', $setting->type)->and('key', '=', $setting->key)->execute(TRUE);
    if (!$found) {
      return NULL;
    }
    $row->byProperty('id', intval($found->id), FALSE);
    return $row;
  }

  /** A Tetra setting a site may override, from its path, or a 404. */
  protected function requireSetting(string $path): object
  {
    $settings = TetraConfig::siteSettings();
    if (!isset($settings[$path])) {
      $this->notFound('Setting', $path);
    }
    return $settings[$path];
  }

  /**
   * GET /core/config/robots
   *
   * The site's robots.txt as crawlers get it (each layer headed by where it
   * came from), to check the site's own rules after changing them.
   */
  public function robotsGET(): void
  {
    $this->reqPermission('VIEW');
    $this->success('robots', (new \Core\Pages\Helpers\PageRenderer())->discoveryFor()->robotsTxt());
  }

  /**
   * POST /core/config/setting {"path": "app.name", "value": "Thyme (DDEV)"}
   *
   * This site's own value for a Tetra setting, which then wins over the
   * app's tetra.json.
   */
  public function settingPOST(): void
  {
    $this->reqPermission('EDIT');
    $setting = $this->requireSetting(strval($this->postValue('path', '')));
    $value = $this->postValue('value', NULL, 'raw');
    if ($setting->value_type === 'number' && is_string($value) && is_numeric($value)) {
      $value = $value + 0;
    }
    if (!TetraConfig::allowed($value, $setting->rule)) {
      $this->error("That isn't an allowed value for {$setting->label}.", 400, 'Config', $setting->path);
    }
    $row = $this->siteRow($setting) ?? new ConfigItem((object) [
      'type' => $setting->type,
      'key' => $setting->key,
      'label' => $setting->label,
      'description' => $setting->description,
      'value_type' => $setting->value_type,
    ]);
    $row->value = $row->castValue($value);
    $row->save();
    $this->success('settings', $this->settings(), "{$setting->label} saved for this site.");
  }

  /**
   * DELETE /core/config/setting/{path}
   *
   * Drop this site's value, so the app's tetra.json (or Tetra's default)
   * applies again.
   */
  public function settingDELETE(string $path = ''): void
  {
    $this->reqPermission('EDIT');
    $setting = $this->requireSetting($path);
    $this->siteRow($setting)?->delete();
    $this->success('settings', $this->settings(), "{$setting->label} is back to the app's value.");
  }

  /**
   * GET /core/config/index
   *
   * @return void JSON Response {"data": {"config": [...]}}
   */
  public function indexGET(): void
  {
    $this->reqPermission('VIEW');
    $this->addData('settings', $this->settings());
    $this->success('config', $this->allConfig());
  }

  /**
   * POST /core/config/index
   *
   * Payload: {"config": [{"id": 1, "value": "..."}, ...]}
   *
   * Only `value` is updated. Every item is validated before
   * anything is saved, so an invalid value saves nothing.
   *
   * @return void JSON Response {"data": {"config": [...]}}
   */
  public function indexPOST(): void
  {
    $this->reqPermission('EDIT');
    $items = $this->postValue('config', []);
    if (!is_array($items)) {
      $this->error('Expected an array of config items', 400);
    }

    $updates = [];
    foreach ($items as $item) {
      $item = (object) $item;
      if (!isset($item->id) || !property_exists($item, 'value')) {
        $this->error('Each config item requires an id and value', 400);
      }
      $config = new ConfigItem();
      if (!$config->byProperty('id', intval($item->id), FALSE)) {
        $this->notFound('Config', $item->id);
      }
      if (!$config->validateValue($item->value)) {
        $this->error("Invalid value for {$config->label} ({$config->key}): expected {$config->value_type}", 400, 'Config', $config->key);
      }
      $config->value = $config->castValue($item->value);
      $updates[] = $config;
    }

    foreach ($updates as $config) {
      $config->save();
    }

    $this->addData('settings', $this->settings());
    $this->success('config', $this->allConfig(), 'Configuration saved');
  }
}
