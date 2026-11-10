<?php
// phpcs:ignoreFile
define('CORE_ROOT', __dir__);

// SERVER_ROOT is the deployed app root -- core/, plugins/, migrations/ and
// public/ are all siblings under it, so by default it's the parent of core/.
// A project that includes Tetra as a submodule keeps core/ at tetra/core, one
// level too deep, so it can name its root in the TETRA_SERVER_ROOT environment
// variable instead (eg. DDEV's web_environment, or Apache's SetEnv).
$serverRoot = getenv('TETRA_SERVER_ROOT');
define('SERVER_ROOT', $serverRoot ? realpath($serverRoot) : realpath(CORE_ROOT . '/..'));

// CONFIG_PATH is resolved independently, walking up from SERVER_ROOT until a
// `config` directory is found. This lets config live outside SERVER_ROOT
// entirely (e.g. one level above the web root, so it's never web-reachable)
// without affecting where plugins/migrations/core -- which stay inside
// SERVER_ROOT -- are located.
$configRoot = SERVER_ROOT;
while (!is_dir($configRoot . '/config')) {
  $parent = dirname($configRoot);
  // The top of the filesystem with nothing found: stop, instead of looping on
  // "/" forever.
  if ($parent === $configRoot) {
    $message = 'Tetra: no config directory found in or above ' . SERVER_ROOT;
    if (PHP_SAPI === 'cli') {
      fwrite(STDERR, $message . PHP_EOL);
      exit(1);
    }
    error_log($message);
    http_response_code(500);
    exit('Configuration not found.');
  }
  $configRoot = $parent;
}
define('CONFIG_PATH', $configRoot . '/config');
