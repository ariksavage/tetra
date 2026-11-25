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

  /** Given to every new user. */
  public bool $default = FALSE;

  /**
   * An API key's own role: edited on the key's page, never given to a person,
   * inherited by another role, or listed on the Roles and Permissions pages.
   */
  public bool $api_key = FALSE;

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
    parent::__construct('Role', 'roles', $data, $flags = []);
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
    // Worked out from roles_permissions, not a column.
    unset($data['permissions']);
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
    $roleIds = array(0, $this->id);
    $hasInherit = TRUE;
    while ($hasInherit) {
      $query = $this->select()->from('roles_inheritance')
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
    $query = $this->select(['permissions.*'])
    ->from('roles_permissions')
    ->leftJoin('permissions', 'permission_id', 'id')
    ->where('roles_permissions.role_id', 'IN', $roleIds);

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
  /**
   * Replace the roles this role inherits. API key roles are never inherited.
   * (Roles::setInherits checks for loops first; an API key's role can't
   * start one, since nothing inherits it.)
   *
   * @param int[] $roleIds
   */
  public function setInherits(array $roleIds): void
  {
    $id = intval($this->id);
    $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds), fn($r) => $r && $r !== $id)));
    if ($roleIds) {
      $roleIds = array_map(fn($r) => intval($r->id), $this->raw('SELECT `id` FROM `roles` WHERE `api_key` = 0 AND `id` IN (' . implode(',', $roleIds) . ')')->executeSelect() ?: []);
    }
    $this->raw("DELETE FROM `roles_inheritance` WHERE `role_id` = {$id}")->execute();
    foreach ($roleIds as $parent) {
      $this->insert(['role_id' => $id, 'inherits_role' => $parent], 'roles_inheritance')->execute();
    }
  }

  /**
   * Replace this role's own permissions (not the ones it inherits).
   *
   * @param int[] $permissionIds Unknown IDs are ignored.
   */
  public function setPermissions(array $permissionIds): void
  {
    $id = intval($this->id);
    $permissionIds = array_values(array_unique(array_filter(array_map('intval', $permissionIds))));
    if ($permissionIds) {
      $permissionIds = array_map(fn($p) => intval($p->id), $this->raw('SELECT `id` FROM `permissions` WHERE `id` IN (' . implode(',', $permissionIds) . ')')->executeSelect() ?: []);
    }
    $this->raw("DELETE FROM `roles_permissions` WHERE `role_id` = {$id}")->execute();
    foreach ($permissionIds as $permissionId) {
      $this->insert(['role_id' => $id, 'permission_id' => $permissionId], 'roles_permissions')->execute();
    }
  }

  /** IDs of the roles this role inherits, directly. */
  public function inheritedRoleIds(): array
  {
    return array_map(fn($r) => intval($r->inherits_role), $this->raw('SELECT `inherits_role` FROM `roles_inheritance` WHERE `role_id` = ' . intval($this->id))->executeSelect() ?: []);
  }

  /** IDs of this role's own permissions, not inherited ones. */
  public function ownPermissionIds(): array
  {
    return array_map(fn($r) => intval($r->permission_id), $this->raw('SELECT `permission_id` FROM `roles_permissions` WHERE `role_id` = ' . intval($this->id))->executeSelect() ?: []);
  }

  public function hasPermission(string $dimension, string $action = ''): bool
  {
    $this->getPermissions();
    if (!isset($this->permissions->$dimension) || !$this->permissions->$dimension) {
      return FALSE;
    }

    // User has some permissions for the dimensions, but no particular action is provided
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
