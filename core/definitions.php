<?php
// phpcs:ignoreFile
define('CORE_ROOT', __dir__);
$root = realpath(CORE_ROOT . '/..');
while (!is_dir($root .'/config')) {
  $root = realpath($root . '/..');

}
define('SERVER_ROOT', $root);
define('PLUGINS_ROOT', SERVER_ROOT . '/core');
define('CONFIG_PATH', SERVER_ROOT . '/config');
