<?php
/**
 * Roles API
 *
 * GET    /core/roles/list        Every role, with how many users have it and what it inherits
 * GET    /core/roles/id/{id}     One role
 * POST   /core/roles/save        Create or update: { "role": { "id", "label", "description", "default", "inherits": [ids] } }
 * DELETE /core/roles/id/{id}     Delete a role (not root, admin or user), taking it from its users
 *
 * Like Drupal's roles screen: a role is a named bundle of permissions,
 * which it can also inherit from other roles. Which permissions each role
 * has is set on the permissions screen (see the Permissions API).
 *
 * PHP version 8.4
 *
 * @category   API
 * @package    Tetra
 * @author     Arik Savage <ariksavage@gmail.com>
 * @since      2026-11-11
 */

namespace Core\Roles\API;

use \Core\Users\Models\UserRole;

class Roles extends \Core\Base\API\Base
{
  /** Tetra's own roles: their keys never change, and they can't be deleted. */
  const BUILT_IN = ['root', 'admin', 'user'];

  public function __construct()
  {
    parent::__construct('roles', '\Core\Users\Models\UserRole', 'Roles', 'Role');
  }

  public function listGET($flags = [])
  {
    $this->reqPermission('VIEW');
    $roles = $this->select()->from('roles')->orderBy('id')->execute() ?: [];
    $counts = [];
    foreach ($this->raw('SELECT `role_id`, COUNT(*) AS `n` FROM `users_roles` GROUP BY `role_id`')->executeSelect() ?: [] as $row) {
      $counts[intval($row->role_id)] = intval($row->n);
    }
    $inherits = $this->inheritance();
    $list = array_map(fn($r) => (object) [
      'id' => intval($r->id),
      'key' => $r->key,
      'label' => $r->label,
      'description' => strval($r->description ?? ''),
      'default' => !!$r->default,
      'built_in' => in_array($r->key, self::BUILT_IN, TRUE),
      'user_count' => $counts[intval($r->id)] ?? 0,
      'inherits' => $inherits[intval($r->id)] ?? [],
    ], $roles);
    $this->success('roles', $list);
  }

  /**
   * Create or update a role. A new role's key is made from its label.
   */
  public function savePOST()
  {
    $data = $this->postValue('role', NULL, 'object');
    $isNew = empty($data->id);
    $this->reqPermission($isNew ? 'CREATE' : 'EDIT');

    $role = new UserRole();
    if (!$isNew) {
      $role->byId(intval($data->id));
    }
    $role->label = trim(strval($data->label ?? $role->label));
    $role->description = trim(strval($data->description ?? $role->description));
    if (isset($data->default)) {
      $role->default = !!$data->default;
    }
    if (!$role->label) {
      $this->error('A role needs a name.', 400, 'Validation');
    }
    if ($isNew) {
      $role->key = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($role->label)), '_');
      if (!$role->key) {
        $this->error('A role needs a name with letters or numbers.', 400, 'Validation');
      }
    }
    $clash = $this->select(['id'])->from('roles')
      ->where('id', '!=', intval($role->id ?? 0))
      ->andGroup()->where('label', '=', $role->label)->or('key', '=', $role->key)->endGroup()
      ->execute(TRUE);
    if ($clash) {
      $this->error('Another role already has that name.', 409, 'Conflict');
    }
    if (!$role->save()) {
      $this->error('Could not save the role.', 500);
    }

    if (isset($data->inherits) && is_array($data->inherits)) {
      $this->setInherits(intval($role->id), array_map('intval', $data->inherits));
    }
    $this->success('role', $role, "Saved the {$role->label} role.");
  }

  /**
   * Delete a role, taking it from its users. Tetra's own roles stay.
   */
  public function idDELETE(int $id)
  {
    $this->reqPermission('DELETE');
    $role = new UserRole();
    $role->byId($id);
    if (in_array($role->key, self::BUILT_IN, TRUE)) {
      $this->error("The {$role->label} role is built in and can't be deleted.", 400, 'Validation');
    }
    foreach (['users_roles' => 'role_id', 'roles_permissions' => 'role_id', 'roles_inheritance' => 'role_id'] as $table => $column) {
      $this->raw("DELETE FROM `{$table}` WHERE `{$column}` = " . intval($id))->execute();
    }
    $this->raw('DELETE FROM `roles_inheritance` WHERE `inherits_role` = ' . intval($id))->execute();
    $label = $role->label;
    $role->delete();
    $this->successMsg("Deleted the {$label} role.");
  }

  /**
   * Set the roles a role inherits. Refuses a loop (a role inheriting itself,
   * directly or through others).
   */
  protected function setInherits(int $roleId, array $inherits): void
  {
    $inherits = array_values(array_unique(array_filter($inherits, fn($id) => $id && $id !== $roleId)));
    $all = $this->inheritance();
    $all[$roleId] = $inherits;
    foreach ($inherits as $start) {
      $seen = [];
      $queue = [$start];
      while ($queue) {
        $next = array_shift($queue);
        if ($next === $roleId) {
          $this->error('A role can\'t inherit from a role that inherits from it.', 400, 'Validation');
        }
        if (isset($seen[$next])) {
          continue;
        }
        $seen[$next] = TRUE;
        $queue = array_merge($queue, $all[$next] ?? []);
      }
    }
    $this->raw('DELETE FROM `roles_inheritance` WHERE `role_id` = ' . intval($roleId))->execute();
    foreach ($inherits as $parent) {
      $this->insert(['role_id' => $roleId, 'inherits_role' => $parent], 'roles_inheritance')->execute();
    }
  }

  /** @return array<int, int[]> Each role's directly inherited roles. */
  protected function inheritance(): array
  {
    $map = [];
    foreach ($this->raw('SELECT `role_id`, `inherits_role` FROM `roles_inheritance`')->executeSelect() ?: [] as $row) {
      $map[intval($row->role_id)][] = intval($row->inherits_role);
    }
    return $map;
  }
}
