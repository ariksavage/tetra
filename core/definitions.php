<?php
// phpcs:ignoreFile
define('CORE_ROOT', __dir__);

// SERVER_ROOT is the deployed app root -- always the parent of core/, since
// that's a fixed structural fact of how deploys lay files out (core/,
// plugins/, migrations/, public/ are all siblings under it).
define('SERVER_ROOT', realpath(CORE_ROOT . '/..'));

// CONFIG_PATH is resolved independently, walking up from SERVER_ROOT until a
// `config` directory is found. This lets config live outside SERVER_ROOT
// entirely (e.g. one level above the web root, so it's never web-reachable)
// without affecting where plugins/migrations/core -- which stay inside
// SERVER_ROOT -- are located.
$configRoot = SERVER_ROOT;
while (!is_dir($configRoot . '/config')) {
  $configRoot = realpath($configRoot . '/..');
}
define('CONFIG_PATH', $configRoot . '/config');
