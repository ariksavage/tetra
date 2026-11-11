<?php
/**
 * Permissions API
 *
 * GET  /core/permissions/matrix   Every permission, every role, and which role has which
 * POST /core/permissions/matrix   Save it: { "grants": { "<roleId>": [permissionIds] } }
 *
 * Like Drupal's permissions screen: one row per permission, grouped by what
 * it's about (its dimension), one column per role. Permissions themselves
 * are defined by Tetra and the app (in migrations), not on this screen.
 *
 * A role also has every permission of the roles it inherits; those show as
 * inherited and are changed on the role they come from.
 *
 * So no one can lock everyone out, the admin role always keeps full control
 * of users, roles and permissions.
 *
 * PHP version 8.4
 *
 * @category   API
 * @package    Tetra
 * @author     Arik Savage <ariksavage@gmail.com>
 * @since      2026-11-11
 */

namespace Core\Permissions\API;

class Permissions extends \Core\Base\API\Base
{
  /** Dimensions whose '*' permission the admin role always keeps. */
  const ADMIN_KEEPS = ['users', 'roles', 'permissions'];

  public function __construct()
  {
    parent::__construct('permissions', '\Core\Users\Models\UserPermission', 'Permissions', 'Permission');
  }

  public function matrixGET()
  {
    $this->reqPermission('VIEW');
    $permissions = $this->select(['id', 'dimension', 'action', 'title', 'description'])->from('permissions')
      ->orderBy('dimension')->orderBy('id')->execute() ?: [];
    foreach ($permissions as $p) {
      $p->id = intval($p->id);
      $p->description = strval($p->description ?? '');
      $p->locked = in_array($p->dimension, self::ADMIN_KEEPS, TRUE) && $p->action === '*';
    }
    $roles = $this->select(['id', 'key', 'label'])->from('roles')->orderBy('id')->execute() ?: [];
    $grants = [];
    foreach ($roles as $role) {
      $role->id = intval($role->id);
      $grants[$role->id] = [];
    }
    foreach ($this->raw('SELECT `role_id`, `permission_id` FROM `roles_permissions`')->executeSelect() ?: [] as $row) {
      $grants[intval($row->role_id)][] = intval($row->permission_id);
    }
    $this->success('matrix', (object) [
      'permissions' => $permissions,
      'roles' => $roles,
      'grants' => (object) $grants,
      'inherited' => (object) $this->inheritedGrants($roles, $grants),
    ]);
  }

  /**
   * Replace each given role's own permissions. Roles not sent are left as
   * they are.
   */
  public function matrixPOST()
  {
    $this->reqPermission('EDIT');
    $grants = $this->postValue('grants', NULL, 'object');
    if (!is_object($grants)) {
      $this->error('No permissions were sent.', 400, 'Validation');
    }
    $validIds = array_map(fn($r) => intval($r->id), $this->select(['id'])->from('permissions')->execute() ?: []);
    $dimensions = implode(', ', array_map(fn($d) => "'{$d}'", self::ADMIN_KEEPS));
    $adminKeeps = array_map(fn($r) => intval($r->id), $this->raw(
      "SELECT `id` FROM `permissions` WHERE `action` = '*' AND `dimension` IN ({$dimensions})"
    )->executeSelect() ?: []);

    foreach ($grants as $roleId => $permissionIds) {
      $roleId = intval($roleId);
      $role = $this->select(['id', 'key'])->from('roles')->where('id', '=', $roleId)->execute(TRUE);
      if (!$role) {
        continue;
      }
      $ids = array_values(array_intersect(array_unique(array_map('intval', (array) $permissionIds)), $validIds));
      if ($role->key === 'admin') {
        $ids = array_values(array_unique(array_merge($ids, $adminKeeps)));
      }
      $this->raw('DELETE FROM `roles_permissions` WHERE `role_id` = ' . $roleId)->execute();
      foreach ($ids as $permissionId) {
        $this->insert(['role_id' => $roleId, 'permission_id' => $permissionId], 'roles_permissions')->execute();
      }
    }
    $this->successMsg('Saved the permissions.');
  }

  /**
   * For each role, the permissions it gets from the roles it inherits (and
   * theirs), not counting its own.
   */
  protected function inheritedGrants(array $roles, array $grants): array
  {
    $parents = [];
    foreach ($this->raw('SELECT `role_id`, `inherits_role` FROM `roles_inheritance`')->executeSelect() ?: [] as $row) {
      $parents[intval($row->role_id)][] = intval($row->inherits_role);
    }
    $inherited = [];
    foreach ($roles as $role) {
      $ids = [];
      $seen = [$role->id => TRUE];
      $queue = $parents[$role->id] ?? [];
      while ($queue) {
        $next = array_shift($queue);
        if (isset($seen[$next])) {
          continue;
        }
        $seen[$next] = TRUE;
        $ids = array_merge($ids, $grants[$next] ?? []);
        $queue = array_merge($queue, $parents[$next] ?? []);
      }
      $inherited[$role->id] = array_values(array_diff(array_unique($ids), $grants[$role->id] ?? []));
    }
    return $inherited;
  }
}
