<?php
/**
 * Configuration API
 *
 * Create, Read, Update, Delete application configuration options
 *
 * PHP version 8.4
 *
 * @category   API
 * @package    Core
 * @author     Arik Savage <ariksavage@gmail.com>
 * @version    1.0
 * @since      2025-01-10
 */
namespace Core\Config\API;

use \Core\Database\MySQL\Query\Select as selectQuery;

class Config extends \Core\Base\API\Base {

  /**
   * Class constructor
   */
  public function __construct()
  {
    $this->requirePermission('config');
    parent::__construct('config', '\Core\Config\Models\Config', 'Config', 'Config');
  }

  /**
   * Get a list of columns to be used for search queries.
   * To be overridden by specific APIs.
   *
   * @return Array<String> List of column names.
   */
  protected function searchColumns(): Array
  {
    return ['label', 'description', 'type', 'value'];
  }

  /**
   * Build a query to list config.
   *
   * @param int $page Page number of results to return.
   * @param int $per  Number of results per page.
   *
   * Override $per to show all options at once
   * Order by type, then by key
   *
   * @return selectQuery  Query object
   */
  protected function listQuery(int $page = 1, int $per = 999999): selectQuery
  {
    $query = parent::listQuery($page, $per);
    $query->orderBy('type')->orderBy('key');
    return $query;
  }

  /**
   * Recursive function to update a menu item and its children
   *   And each child's children...
   *     And each child's children...
   *
   * @param  object $item Menu item
   * @return Array<int> Array of IDs that have been processed.
   */
  protected function updateMenuItems($item)
  {
    $ids = [];
    $data = (array) $item;
    unset($data['children']);
    unset($data['id']);
    if (isset($item->id)) {
      $q = $this->update($data, 'menu')->where('id', '=', $item->id);
      $q->execute();
    } else {
      $q = $this->insert($data, 'menu');
      $item->id = $q->execute();
      foreach ($item->children as &$child) {
        $child->parent = $item->id;
      }
    }
    $ids[] = $item->id;

    if ($item->children) {
      foreach ($item->children as $child) {
        $childIds = $this->updateMenuItems($child);
        $ids = array_merge($ids, $childIds);
      }
    }
    return $ids;
  }

  /**
   * Handle a PATCH request to update the menu
   * @return JSON response.
   */
  public function menuPATCH()
  {
    $this->requirePermission('menu', 'UPDATE');
    $menu = $this->postValue('menu');
    $ids = [];
    foreach ($menu->children as $item) {
      $childIds = $this->updateMenuItems($item);
        $ids = array_merge($ids, $childIds);
    }
    asort($ids);
    // Delete items that were left out
    $deleteQ = $this->deleteQuery('menu')->where('id', 'NOT IN', $ids);
    $deleteQ->execute();
    $this->success('menu', $menu);
  }

  /**
   * Handle a GET request to retrieve all configuration of a given type
   *
   * @param  string $type Type of configuration to find.
   *
   * @return JSON response.
   */
  public function typeGET($type)
  {
    $this->reqPermission('VIEW');
    $config = $this->select()
      ->where('type', '=', $type)
      ->execute(FALSE, '\Core\Config\Models\Config');
    $this->success('config', $config);
  }

  /**
   * Handle PATCH request to update a given config value
   *
   * @param  int $id Config option's ID
   *
   * @return JSON response
   */
  public function valuePATCH(int $id)
  {
    $this->reqPermission('UPDATE');
    $value = $this->postValue('value');

    $config = new \Core\Config\Models\Config();
    $config->byId($id);
    $config->setValue($value);
    $this->success('config', $config);
  }
}
