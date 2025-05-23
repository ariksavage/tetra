<?php

namespace Core\App\API;

use \Core\Users\Models\User;

class App extends \Core\Base\API\Base {

  public function __construct()
  {
    $this->data = new \stdClass();
  }

  /**
   * Check if authorization is required for this API.
   * May be overridden to allow public access. Not recommended.
   *
   * @return Boolean TRUE if login is required
   */
  public function requiresAuth(): bool
  {
    return FALSE;
  }

  /**
   * Load basic configuration and menu data
   * needed to initialize the application.
   */
  public function indexGET()
  {
    $data = new \stdClass();
    $data->config = new \stdClass();
    $config = $this->select()->from('config')->where('type', '=', 'application')->execute(FALSE);
    foreach ($config as $item) {
      $key = $item->key;
      switch ($item->value_type) {
        case 'number':
          $value = floatval($item->value);
          break;
        case 'object':
          $value = json_decode($item->value);
          break;
        case 'boolean':
          $value = !!$item->value;
          break;
        default:
          $value = $item->value;
          break;
      }
      $data->config->$key = $value;
    }
    $this->success('app', $data);
  }

  /**
   * Get child menu items from the database for a given parent ID
   * @param  int   $parentId  ID of the parent for which to get children
   * @return array            Array of child items, populated recursively.
   */
  protected function getMenuChildren(int $parentID = 0)
  {
    $items = $this->select()->from('menu')
    ->where('parent', '=', $parentID)
    ->orderBy('weight')
    ->execute();
    foreach ($items as &$item) {
      $item->id = intval($item->id);
      $item->parent = intval($item->parent);
      $item->weight = intval($item->weight);
      $item->children = $this->getMenuChildren($item->id);
    }
    return $items;
  }

  /**
   * Get The menu starting from the given root.
   * If no root is provided, The whole menu will be generated.
   * @param  string $root Root path from which to build the menu
   * @return array        Array of menu items
   */
  protected function menuTree(string $root = '')
  {
    $root = '/' . $root;
    $parent = new \stdClass();
    $parent->path = $root;
    $parent->icon = NULL;
    $parent->title = NULL;
    if ($root == '/') {
      $parent->id = 0;
    } else {
      $parent = $this->select()
      ->from('menu')
      ->where('path', '=', $root)
      ->execute(TRUE);
    }
    $parent->children = $this->getMenuChildren($parent->id);
    return $parent;
  }

  /**
   * Handle a GET request to return the menu for a given root
   * @param  string $root Root path from which to build the menu
   * @return JSON Response
   */
  public function menuTreeGET($root = '')
  {
    $menu = $this->menuTree($root);
    $this->success('menu', $menu);
  }

  /**
   * Handle POST request for user login.
   * @return JSON Response
   */
  public function loginPOST()
  {
    $username = $this->postValue('username');
    $password = $this->postValue('password');
    $sessionExpiresDays = $this->configValue('session_expires_days');
    $user = new User();
    if ($token = $user->login($username, $password, $sessionExpiresDays)) {
      $this->addData('token', $token);
      $this->success('user', $user, "Welcome, {$user->name()}");
    } else {
      $this->error('Login failed', 401, 'Login');
    }
  }
}
?>
