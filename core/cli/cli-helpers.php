<?php
/**
 * Helpers shared by the command line scripts: stop with a message, and ask
 * for a password without showing it.
 *
 * PHP version 8.4
 *
 * @category   CLI
 * @package    Tetra
 * @author     Arik Savage <ariksavage@gmail.com>
 * @since      2026-11-23
 */

if (PHP_SAPI !== 'cli') {
  http_response_code(404);
  exit;
}

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

/**
 * Ask for a new password twice, and stop unless it's long enough and the
 * two match.
 */
function askNewPassword(): string
{
  $password = askHidden('New password (at least 8 characters): ');
  if (strlen($password) < 8) {
    fail('Passwords need at least 8 characters.');
  }
  if (askHidden('Same password again: ') !== $password) {
    fail("The passwords don't match.");
  }
  return $password;
}
