<?php
/**
 * API keys API
 *
 * GET    /core/api-keys/list[?all=1]     Your keys (everyone's, with all=1, for someone with api_keys *)
 * GET    /core/api-keys/id/{id}          One key, with what its edit form needs: the roles it inherits and
 *                                        may inherit, its own and inherited permissions, and what it can
 *                                        actually do (its role's permissions that its owner also has)
 * POST   /core/api-keys/create           { "name", "expires_days": 0|30|90|365, "inherits": [roleIds],
 *                                        "permissions": [ids] } A new key; the token is in the answer, once
 * POST   /core/api-keys/save             { "id", "name"?, "expires_days"?, "inherits"?, "permissions"? }
 * POST   /core/api-keys/revoke           { "id" } It stops working at once; its log is kept
 * DELETE /core/api-keys/id/{id}          A revoked key, with its role and log
 * GET    /core/api-keys/log/{id}         What it changed, newest first
 * GET    /core/api-keys/me               For a key: which key it is and what it can do. The only call
 *                                        here a key may make.
 *
 * A key is managed by its owner (with the api_keys permissions) or by
 * someone with api_keys *. Its permissions are its own role's: see
 * ApiKey.model.php.
 *
 * PHP version 8.4
 *
 * @category   API
 * @package    Tetra
 * @author     Arik Savage <ariksavage@gmail.com>
 * @since      2026-11-25
 */

namespace Core\ApiKeys\API;

use \Core\ApiKeys\Models\ApiKey;
use \Core\Users\Models\User;
use \Core\Users\Models\UserRole;

class ApiKeys extends \Core\Base\API\Base
{
  /**
   * What a key never reaches, whatever its role says (those APIs refuse
   * keys: see Base::allowsApiKeys), so they aren't offered.
   */
  const NEVER = ['users', 'roles', 'permissions', 'config', 'api_keys'];

  /** How long a new key lasts, in days; 0 is never expiring. */
  const EXPIRY_CHOICES = [0, 30, 90, 365];

  public function __construct()
  {
    parent::__construct('api_keys', '\Core\ApiKeys\Models\ApiKey', 'Keys', 'Key');
  }

  /** A key may only ask who it is. */
  public function allowsApiKeys(string $action): bool
  {
    return $action === 'me';
  }

  public function listGET($flags = [])
  {
    $this->reqPermission('VIEW');
    $me = $this->getCurrentUser();
    $all = $this->getValue('all', FALSE, 'boolean') && $me->hasPermission('api_keys', '*');
    $query = $this->select(['id'])->from('api_keys');
    if (!$all) {
      $query->where('user_id', '=', intval($me->id));
    }
    $keys = [];
    foreach ($query->orderBy('id', 'DESC')->execute() ?: [] as $row) {
      $key = new ApiKey();
      $key->byId(intval($row->id));
      $item = $key->jsonSerialize();
      if ($all) {
        $owner = new User();
        $owner->byId($key->user_id);
        $item['owner'] = $owner->name();
      }
      $keys[] = $item;
    }
    $this->success('keys', $keys);
  }

  public function idGET(int $id)
  {
    $this->reqPermission('VIEW');
    $key = $this->ownKey($id);
    $this->success('key', $this->detail($key));
  }

  public function createPOST()
  {
    $this->reqPermission('CREATE');
    $name = trim(strval($this->postValue('name', '')));
    if (!$name) {
      $this->error('Give the key a name, eg. what will use it.', 400, 'Validation');
    }
    $days = $this->expiryDays($this->postValue('expires_days', 0, 'integer'));
    $token = '';
    $key = ApiKey::make(intval($this->getCurrentUser()->id), $name, $days, $token);
    $this->applyRole($key);
    $this->addData('token', $token);
    $this->success('key', $this->detail($key), "Made the key \"{$name}\". Copy it now: it won't be shown again.");
  }

  public function savePOST()
  {
    $this->reqPermission('EDIT');
    $key = $this->ownKey($this->postValue('id', 0, 'integer'));
    if ($key->status() === 'revoked') {
      $this->error('A revoked key can\'t be changed.', 400, 'Validation');
    }
    $data = $this->getPostData();
    if (isset($data->name)) {
      $name = trim(strval($data->name));
      if (!$name) {
        $this->error('A key needs a name.', 400, 'Validation');
      }
      $key->name = $name;
    }
    if (isset($data->expires_days)) {
      $days = $this->expiryDays(intval($data->expires_days));
      $key->date_expires = $days ? strtotime("+{$days} days") : 0;
    }
    $key->save();
    $role = new UserRole();
    $role->byId($key->role_id);
    if ($role->id && $role->label !== $key->roleLabel()) {
      $this->update(['label' => $key->roleLabel()], 'roles')->where('id', '=', $role->id)->execute();
    }
    $this->applyRole($key);
    $this->success('key', $this->detail($key), "Saved \"{$key->name}\".");
  }

  public function revokePOST()
  {
    $this->reqPermission('DELETE');
    $key = $this->ownKey($this->postValue('id', 0, 'integer'));
    if (!$key->date_revoked) {
      $this->update(['date_revoked' => date('Y-m-d H:i:s')], 'api_keys')->where('id', '=', $key->id)->execute();
      $key->byId(intval($key->id));
    }
    $this->success('key', $this->detail($key), "Revoked \"{$key->name}\": it no longer works.");
  }

  public function idDELETE(int $id)
  {
    $this->reqPermission('DELETE');
    $key = $this->ownKey($id);
    if ($key->status() !== 'revoked') {
      $this->error('Revoke the key first.', 400, 'Validation');
    }
    $roleId = intval($key->role_id);
    $keyId = intval($key->id);
    $statements = [
      "DELETE FROM `roles_permissions` WHERE `role_id` = {$roleId}",
      "DELETE FROM `roles_inheritance` WHERE `role_id` = {$roleId} OR `inherits_role` = {$roleId}",
      "DELETE FROM `users_roles` WHERE `role_id` = {$roleId}",
      "DELETE FROM `roles` WHERE `id` = {$roleId} AND `api_key` = 1",
      "DELETE FROM `api_key_log` WHERE `api_key_id` = {$keyId}",
      "DELETE FROM `api_keys` WHERE `id` = {$keyId}",
    ];
    $this->raw('START TRANSACTION')->execute();
    try {
      foreach ($statements as $sql) {
        $this->raw($sql)->execute();
      }
      $this->raw('COMMIT')->execute();
    } catch (\Throwable $e) {
      $this->raw('ROLLBACK')->execute();
      $this->error('Couldn\'t delete the key. Nothing was changed.', 500);
    }
    $this->successMsg("Deleted \"{$key->name}\" and its log.");
  }

  public function logGET(int $id)
  {
    $this->reqPermission('VIEW');
    $key = $this->ownKey($id);
    $rows = $this->raw('SELECT `id`, `method`, `path`, `status`, UNIX_TIMESTAMP(`date_created`) AS `date` FROM `api_key_log`
      WHERE `api_key_id` = ' . intval($key->id) . ' ORDER BY `id` DESC LIMIT 500')->executeSelect() ?: [];
    $this->success('log', array_map(fn($r) => (object) [
      'id' => intval($r->id),
      'method' => $r->method,
      'path' => $r->path,
      'status' => intval($r->status),
      'date' => intval($r->date),
    ], $rows));
  }

  /** For a key: which key it is, whose, until when, and what it can do. */
  public function meGET()
  {
    $me = $this->getCurrentUser();
    $key = $me ? $me->apiKey() : NULL;
    if (!$key) {
      $this->error('This is for API keys: send one as "Authorization: Bearer tetra_…".', 400, 'Validation');
    }
    $this->success('key', (object) [
      'name' => $key->name,
      'display' => ApiKey::PREFIX . $key->prefix . '…',
      'owner' => $me->name(),
      'expires' => $key->date_expires ?: NULL,
      'can' => $this->effectiveAccess($key)->can,
    ]);
  }

  // ---- Helpers ----

  /** A key the current user may manage: their own, or anyone's with api_keys *. */
  protected function ownKey(int $id): ApiKey
  {
    $key = new ApiKey();
    $key->byId($id);
    $me = $this->getCurrentUser();
    if (!$key->id || (intval($key->user_id) !== intval($me->id) && !$me->hasPermission('api_keys', '*'))) {
      $this->error('That key wasn\'t found.', 404);
    }
    return $key;
  }

  protected function expiryDays(int $days): int
  {
    if (!in_array($days, self::EXPIRY_CHOICES, TRUE)) {
      $this->error('A key lasts 30, 90 or 365 days, or never expires.', 400, 'Validation');
    }
    return $days;
  }

  /** Set the key's role from the request's inherits and permissions, where sent. */
  protected function applyRole(ApiKey $key): void
  {
    $data = $this->getPostData();
    $role = new UserRole();
    $role->byId($key->role_id);
    if (!$role->id) {
      return;
    }
    if (isset($data->inherits) && is_array($data->inherits)) {
      $role->setInherits($data->inherits);
    }
    if (isset($data->permissions) && is_array($data->permissions)) {
      // Never the ones a key can't use.
      $allowed = array_map(fn($p) => intval($p->id), $this->offeredPermissions());
      $role->setPermissions(array_values(array_intersect(array_map('intval', $data->permissions), $allowed)));
    }
  }

  /** Permissions a key may be given: all but those for what keys never reach. */
  protected function offeredPermissions(): array
  {
    $never = implode(', ', array_map(fn($d) => "'{$d}'", self::NEVER));
    return $this->raw("SELECT `id`, `dimension`, `action`, `title`, `description` FROM `permissions`
      WHERE `dimension` NOT IN ({$never}) ORDER BY `dimension`, `id`")->executeSelect() ?: [];
  }

  /**
   * Everything the key's page shows: the key, its inherits and permissions,
   * the choices, and what it can actually do.
   */
  protected function detail(ApiKey $key): object
  {
    $role = $key->role();
    $own = $role->ownPermissionIds();
    $all = [];
    foreach ((array) $role->permissions as $actions) {
      foreach ((array) $actions as $permission) {
        $all[] = intval($permission->id);
      }
    }
    $permissions = array_map(fn($p) => (object) [
      'id' => intval($p->id),
      'dimension' => $p->dimension,
      'action' => $p->action,
      'title' => $p->title,
      'description' => strval($p->description ?? ''),
    ], $this->offeredPermissions());
    $roles = array_map(fn($r) => (object) [
      'id' => intval($r->id),
      'label' => $r->label,
      'description' => strval($r->description ?? ''),
    ], $this->raw('SELECT `id`, `label`, `description` FROM `roles` WHERE `api_key` = 0 ORDER BY `id`')->executeSelect() ?: []);
    $access = $this->effectiveAccess($key);

    $item = (object) $key->jsonSerialize();
    $item->inherits = $role->inheritedRoleIds();
    $item->permission_ids = $own;
    $item->inherited_permission_ids = array_values(array_diff(array_unique($all), $own));
    $item->roles = $roles;
    $item->permissions = $permissions;
    $item->can = $access->can;
    $item->beyond_owner = $access->beyondOwner;
    return $item;
  }

  /**
   * What the key can really do: by dimension, the actions its role allows
   * that its owner also has. beyondOwner lists what its role allows that the
   * owner doesn't (so the key can't either).
   */
  protected function effectiveAccess(ApiKey $key): object
  {
    $role = $key->role();
    $owner = new User();
    $owner->byId($key->user_id);
    $byDimension = [];
    foreach ($this->offeredPermissions() as $p) {
      if ($p->action !== '*') {
        $byDimension[$p->dimension][] = $p->action;
      }
    }
    $can = [];
    $beyond = [];
    foreach ($byDimension as $dimension => $actions) {
      foreach ($actions as $action) {
        if (!$role->hasPermission($dimension, $action)) {
          continue;
        }
        if ($owner->hasPermission($dimension, $action)) {
          $can[$dimension][] = $action;
        } else {
          $beyond[$dimension][] = $action;
        }
      }
    }
    return (object) ['can' => (object) $can, 'beyondOwner' => (object) $beyond];
  }
}
