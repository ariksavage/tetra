<?php
/**
 * Set a user's login from the command line: a new password, and optionally a
 * new username or email address. For when no one can log in, eg. a local
 * copy whose password nobody knows. Asks for the password without showing
 * it, and logs the user out everywhere.
 *
 *   php tetra/core/cli/set-login.php                      the first root user
 *   php tetra/core/cli/set-login.php --user=arik          by username, email or ID
 *   php tetra/core/cli/set-login.php --user=devroot --username=arik --email=me@example.com
 *   php tetra/core/cli/set-login.php --username=arik --keep-password
 *
 * PHP version 8.4
 *
 * @category   CLI
 * @package    Tetra
 * @author     Arik Savage <ariksavage@gmail.com>
 * @since      2026-11-23
 */

require_once(__DIR__ . '/cli-helpers.php');
require_once(__DIR__ . '/../bootstrap.php');

$options = getopt('', ['user:', 'username:', 'email:', 'keep-password']);
$keepPassword = array_key_exists('keep-password', $options);
if ($keepPassword && empty($options['username']) && empty($options['email'])) {
  fail('Nothing to change: give --username or --email, or leave out --keep-password to set the password.');
}
if (!empty($options['email']) && !filter_var($options['email'], FILTER_VALIDATE_EMAIL)) {
  fail("That email address doesn't look right.");
}

class SetLogin extends \Core\Base\API\Base
{
  public function __construct()
  {
    parent::__construct('users', '\Core\Users\Models\User', 'Users', 'User');
  }

  /** Run from the server's command line, so there's no one to log in. */
  public function requiresAuth(): bool
  {
    return FALSE;
  }

  /** The user with this username, email address or ID, or 0. */
  public function find(string $who): int
  {
    $query = $this->select(['id'])->from('users')
      ->where('username', '=', $who)->or('email', '=', $who);
    if (ctype_digit($who)) {
      $query->or('id', '=', intval($who));
    }
    $user = $query->execute(TRUE);
    return $user ? intval($user->id) : 0;
  }

  /** The first user with the root role, or 0. */
  public function firstRoot(): int
  {
    $user = $this->raw("SELECT `ur`.`user_id` AS `id` FROM `users_roles` `ur`
      JOIN `roles` `r` ON `r`.`id` = `ur`.`role_id`
      WHERE `r`.`key` = 'root' ORDER BY `ur`.`user_id` LIMIT 1")->executeSelect(TRUE);
    return $user ? intval($user->id) : 0;
  }

  /** Whether another user already has this username or email address. */
  public function takenByOther(string $column, string $value, int $userId): bool
  {
    return !!$this->select(['id'])->from('users')
      ->where($column, '=', $value)->and('id', '!=', $userId)
      ->execute(TRUE);
  }
}

$helper = new SetLogin();
$userId = !empty($options['user']) ? $helper->find($options['user']) : $helper->firstRoot();
if (!$userId) {
  fail(!empty($options['user'])
    ? "No user has the username, email address or ID '{$options['user']}'."
    : 'There is no root user. Make one with create-user.php.');
}

$user = new \Core\Users\Models\User();
$user->byId($userId);
echo "Setting the login for {$user->username} ({$user->email}, user {$user->id})." . PHP_EOL;

foreach (['username', 'email'] as $field) {
  if (!empty($options[$field])) {
    if ($helper->takenByOther($field, $options[$field], $user->id)) {
      fail("Another user already has the {$field} {$options[$field]}.");
    }
    $user->$field = $options[$field];
  }
}
if (!$keepPassword) {
  $user->setPassword(askNewPassword());
}
// A user who can't log in at all is no use here: make sure it's active.
$user->status = 'active';
if (!$user->save()) {
  fail('Could not save the user.');
}
$user->endAllSessions();

$changed = array_filter([
  !empty($options['username']) ? "username {$user->username}" : '',
  !empty($options['email']) ? "email {$user->email}" : '',
  $keepPassword ? '' : 'a new password',
]);
echo "Saved: " . implode(', ', $changed) . ". {$user->username} is logged out everywhere." . PHP_EOL;
