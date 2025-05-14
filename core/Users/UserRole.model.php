<?php
/**
 * User Rolemodel
 *
 * Represents a role with permissions.
 *
 * PHP version 8.4
 *
 * @category   Model
 * @package    Core
 * @author     Arik Savage <ariksavage@gmail.com>
 * @version    1.0
 * @since      2025-01-05
 */

namespace Core\Users\Models;

class UserRole extends \Core\Base\Models\Base {

  /**
   * Shorthand string for this role
   * @var String
   */
  public string $key = '';

  /**
   * Shorthand string for this role
   * @var String
   */
  public string $label = '';

  /**
   * Shorthand string for this role
   * @var String
   */
  public string $description = '';

  public object $permissions;

  /**
   * Construct the model
   *
   * @param string      $label Singular name for this item, eg "User"
   * @param string      $table Database table where this item is stored, eg "users"
   * @param object|null $data  Data to be mapped onto this item
   * @param array       $flags Additional parameters.
   *
   * @return            $this
   */
  public function __construct(object|null $data = NULL, array $flags = [])
  {
    parent::__construct('Role', 'user_roles', $data, $flags = []);
    $this->permissions = new \stdClass();
    $this->getPermissions();
    return $this;
  }


  /**
   * Convert the model to an array for use in a query.
   *
   * And apply any other necessary business logic
   * before saving
   *
   * - Ensure the user's password is hashed, if provided.
   *
   * @return Array
   */
  public function prepareSave()
  {
    $data = parent::prepareSave();
    return $data;
  }

  /**
   * Load this role's permissions from the database.
   *
   * Defines permissions as an associative array of objects,
   * for ease of finding
   *
   * @return void
   */
  public function getPermissions()
  {
    $roleIds = array($this->id);
    $hasInherit = TRUE;
    while ($hasInherit) {
      $query = $this->select()->from('user_role_inherit_permissions')
      ->where('role_id', 'IN', $roleIds)
      ->and('inherits_role', 'NOT IN', $roleIds);
      $inherits = $query->execute(FALSE);
      if (count($inherits) > 0) {
        foreach ($inherits as $inherit) {
          $roleIds[] = $inherit->inherits_role;
        }
        $hasInherit = TRUE;
      } else {
        $hasInherit = FALSE;
      }
    }
    $query = $this->select(['user_permissions.*'])
    ->from('user_role_permissions_assignments')
    ->leftJoin('user_permissions', 'permission_id', 'id')
    ->where('user_role_permissions_assignments.role_id', 'IN', $roleIds);

    $permissions = $query->execute(FALSE, '\Core\Users\Models\UserPermission');
    foreach ($permissions as $permission) {
      $dimension = $permission->dimension;
      if (!isset($this->permissions->$dimension)) {
        $this->permissions->$dimension = new \stdClass();
      }
      $action = $permission->action;
      $this->permissions->$dimension->$action = $permission;
    }
  }

  /**
   * Test if this role has a given permission.
   *
   * If the required permission is VIEW OWN,
   *   The VIEW action will also be allowed.
   *
   * If the role has ALL (*) permissions for a dimension
   *   It will be allowed for any action.
   *
   * @param  string  $dimension Permission's dimension - general category
   * @param  string  $action    Specific action being tested
   * @return boolean            TRUE if the role contains this permission
   */
  public function hasPermission(string $dimension, string $action = ''): bool
  {
    $this->getPermissions();
    if (!isset($this->permissions->$dimension) || !$this->permissions->$dimension) {
      return FALSE;
    }

    if (!$action) {
      return TRUE;
    }

    // ALL permissions are allowed for the dimension.
    $all = '*';
    if (isset($this->permissions->$dimension->$all)) {
      return TRUE;
    }

    // If the permission required is VIEW OWN, VIEW will be allowed
    if (stristr($action, ' OWN')) {
      $parentAction = str_replace(' OWN', '', $action);
      if (isset($this->permissions->$dimension->$parentAction)) {
        return TRUE;
      }
    }
    return isset($this->permissions->$dimension->$action);
  }
}
