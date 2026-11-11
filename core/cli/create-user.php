<?php
/**
 * Create a user from the command line, such as the first admin on a new
 * server. Asks for the password, without showing it, so it never appears in
 * the shell history or the process list.
 *
 *   php tetra/core/cli/create-user.php --username=arik --email=me@example.com \
 *     --first=Arik --last=Savage [--role=root]
 *
 * --role is a role's key (root, admin, user...); it defaults to root.
 *
 * PHP version 8.4
 *
 * @category   CLI
 * @package    Tetra
 * @author     Arik Savage <ariksavage@gmail.com>
 * @since      2026-11-11
 */

if (PHP_SAPI !== 'cli') {
  http_response_code(404);
  exit;
}

require_once(__DIR__ . '/../bootstrap.php');

function fail(string $message): never
{
  fwrite(STDERR, $message . PHP_EOL);
  exit(1);
}

function askHidden(string $prompt): string
{
  fwrite(STDOUT, $prompt);
  $tty = stream_isatty(STDIN);
  if ($tty) {
    shell_exec('stty -echo');
  }
  $value = rtrim(strval(fgets(STDIN)), "\r\n");
  if ($tty) {
    shell_exec('stty echo');
    fwrite(STDOUT, PHP_EOL);
  }
  return $value;
}

$options = getopt('', ['username:', 'email:', 'first:', 'last:', 'role:']);
foreach (['username', 'email', 'first', 'last'] as $required) {
  if (empty($options[$required])) {
    fail("Missing --{$required}. Usage: php create-user.php --username= --email= --first= --last= [--role=root]");
  }
}
if (!filter_var($options['email'], FILTER_VALIDATE_EMAIL)) {
  fail("That email address doesn't look right.");
}

class CreateUser extends \Core\Base\API\Base
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

  public function roleId(string $key): int
  {
    $role = $this->select(['id'])->from('roles')->where('key', '=', $key)->execute(TRUE);
    return $role ? intval($role->id) : 0;
  }

  public function taken(string $username, string $email): bool
  {
    return !!$this->select(['id'])->from('users')
      ->where('username', '=', $username)->or('email', '=', $email)
      ->execute(TRUE);
  }
}

$helper = new CreateUser();
$roleKey = $options['role'] ?? 'root';
$roleId = $helper->roleId($roleKey);
if (!$roleId) {
  fail("There's no role with the key '{$roleKey}'. Have the migrations run?");
}
if ($helper->taken($options['username'], $options['email'])) {
  fail('A user with that username or email address already exists.');
}

$password = askHidden('Password (at least 8 characters): ');
if (strlen($password) < 8) {
  fail('Passwords need at least 8 characters.');
}
if (askHidden('Same password again: ') !== $password) {
  fail("The passwords don't match.");
}

$user = new \Core\Users\Models\User();
$user->username = $options['username'];
$user->email = $options['email'];
$user->first_name = $options['first'];
$user->last_name = $options['last'];
$user->status = 'active';
$user->setPassword($password);
if (!$user->save()) {
  fail('Could not save the user.');
}
$user->updateRoles([$roleId]);

echo "Created {$user->username} (user {$user->id}) with the {$roleKey} role." . PHP_EOL;
