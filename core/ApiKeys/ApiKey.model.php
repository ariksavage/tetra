<?php
/**
 * API key model
 *
 * A long-lived key a person makes so a program (eg. Claude) can use the API
 * as them. A request sends it like a login token:
 *
 *   Authorization: Bearer tetra_0123abcd…
 *
 * - Only a SHA-256 of the key is stored; the key itself is shown once, when
 *   it's made.
 * - What it may do is its own role (roles.api_key = 1): permissions given to
 *   it directly, plus every permission of the roles it inherits. It never
 *   gets more than its owner has (see BasicUser::hasPermission), and never
 *   reaches Tetra's account, user, role, permission, settings or key APIs
 *   (see Base::allowsApiKeys).
 * - Revoked or expired, it stops working at once.
 *
 * PHP version 8.4
 *
 * @category   Model
 * @package    Tetra
 * @author     Arik Savage <ariksavage@gmail.com>
 * @since      2026-11-25
 */

namespace Core\ApiKeys\Models;

class ApiKey extends \Core\Base\Models\Base
{
  /** Every key starts with this, so one is easy to recognise (and to spot if leaked). */
  const PREFIX = 'tetra_';

  /** How long the log of a key's changes is kept. */
  const LOG_DAYS = 90;

  public int $user_id = 0;

  /** Its own role: what it may do. */
  public int $role_id = 0;

  public string $name = '';

  /** The first characters after tetra_, to tell keys apart. */
  public string $prefix = '';

  public string $token_hash = '';

  public int $date_last_used = 0;

  public string $last_used_ip = '';

  /** 0: never expires. */
  public int $date_expires = 0;

  public int $date_revoked = 0;

  public function __construct(object|NULL $data = NULL, array $flags = [])
  {
    parent::__construct('API key', 'api_keys', $data, $flags);
    return $this;
  }

  /** The hash is never sent. */
  public function jsonSerialize(): mixed
  {
    $data = parent::jsonSerialize();
    unset($data['token_hash']);
    $data['status'] = $this->status();
    $data['display'] = self::PREFIX . $this->prefix . '…';
    return $data;
  }

  public function toArray(): array
  {
    return parent::jsonSerialize();
  }

  /** active, expired or revoked. */
  public function status(): string
  {
    if ($this->date_revoked) {
      return 'revoked';
    }
    if ($this->date_expires && $this->date_expires <= time()) {
      return 'expired';
    }
    return 'active';
  }

  public function isUsable(): bool
  {
    return $this->id && $this->status() === 'active';
  }

  /**
   * Make a key for a person, with a role of its own (no permissions until
   * some are given). Returns the key; the token is in $token, and is never
   * available again.
   */
  public static function make(int $userId, string $name, int $expiresDays, string &$token): ApiKey
  {
    $token = self::PREFIX . bin2hex(random_bytes(24));
    $key = new ApiKey();
    $key->user_id = $userId;
    $key->name = $name;
    $key->prefix = substr($token, strlen(self::PREFIX), 8);
    $key->token_hash = hash('sha256', $token);
    $key->date_expires = $expiresDays > 0 ? strtotime("+{$expiresDays} days") : 0;
    $key->save();

    $role = new \Core\Users\Models\UserRole();
    $role->key = 'api_key_' . $key->id;
    $role->label = $key->roleLabel();
    $role->description = 'The permissions of an API key. Managed on the key, not here.';
    $role->api_key = TRUE;
    $role->save();
    $key->role_id = intval($role->id);
    $key->save();
    return $key;
  }

  /** "API key: Claude (0123abcd)": unique, and says what it is if ever seen. */
  public function roleLabel(): string
  {
    return "API key: {$this->name} ({$this->prefix})";
  }

  /**
   * The usable key for a token, or NULL: unknown, revoked and expired keys
   * don't count.
   */
  public static function fromToken(string $token): ?ApiKey
  {
    if (!str_starts_with($token, self::PREFIX)) {
      return NULL;
    }
    $key = new ApiKey();
    $row = $key->select(['id'])->from('api_keys')
      ->where('token_hash', '=', hash('sha256', $token))
      ->execute(TRUE);
    if (!$row) {
      return NULL;
    }
    $key->byId(intval($row->id));
    return $key->isUsable() ? $key : NULL;
  }

  /** Its role, with its permissions (its own and inherited) loaded. */
  public function role(): \Core\Users\Models\UserRole
  {
    $role = new \Core\Users\Models\UserRole();
    $role->byId($this->role_id);
    $role->permissions = new \stdClass();
    $role->getPermissions();
    return $role;
  }

  /** Note it was used, from where: at most once a minute. */
  public function touch(): void
  {
    if ($this->date_last_used && time() - $this->date_last_used < 60) {
      return;
    }
    $this->update([
      'date_last_used' => date('Y-m-d H:i:s'),
      'last_used_ip' => substr(strval($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
    ], 'api_keys')->where('id', '=', $this->id)->execute();
  }

  /** Record a change it made (or tried to). Older entries are cleared now and then. */
  public function log(string $method, string $path, int $status): void
  {
    $this->insert([
      'api_key_id' => $this->id,
      'method' => substr($method, 0, 10),
      'path' => substr($path, 0, 512),
      'status' => $status,
    ], 'api_key_log')->execute();
    if (random_int(1, 50) === 1) {
      $this->raw('DELETE FROM `api_key_log` WHERE `date_created` < NOW() - INTERVAL ' . self::LOG_DAYS . ' DAY')->execute();
    }
  }
}
