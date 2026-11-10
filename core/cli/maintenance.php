<?php

namespace Core;

require_once(__dir__ . '/../bootstrap.php');

// Run Tetra's own migrations (the base tables), then the app's. A migration
// skips itself once complete, so every one is safe to meet again. In a deploy
// the two folders can be the same one; it is only read once.
$folders = array_unique(array_filter([
  realpath(CORE_ROOT . '/../migrations'),
  realpath(SERVER_ROOT . '/migrations'),
]));

foreach ($folders as $migrationsDir) {
  $migrations = array_diff(scandir($migrationsDir), ['.', '..', '.DS_Store']);
  asort($migrations);

  foreach ($migrations as $migration) {
    $name = preg_replace('/^[0-9]+-|\.migration/', '', $migration);
    $class = '\\Core\\Migrations\\' . $name;
    require_once($migrationsDir . '/' . $migration);
    $migration = new $class();
  }
}
