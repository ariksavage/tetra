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

/**
 * Class Config
 *
 * Lists and updates rows in the config table.
 *
 * Access is controlled by the `config` permission dimension
 * (granted to admin, and inherited by root).
 *
 * Rows are never created or deleted here; new options are
 * added by migrations and appear automatically.
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
    return $this->select()
      ->from('config')
      ->orderBy('type')
      ->orderBy('label')
      ->execute(FALSE, '\Core\Config\Models\Config');
  }

  /**
   * GET /core/config/index
   *
   * @return void JSON Response {"data": {"config": [...]}}
   */
  public function indexGET(): void
  {
    $this->reqPermission('VIEW');
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

    $this->success('config', $this->allConfig(), 'Configuration saved');
  }
}
