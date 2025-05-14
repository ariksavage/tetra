<?php

namespace kdCore;

require_once(__dir__ . '/../bootstrap.php');

// look for migrations and run them if not manual

$migrationsDir = SERVER_ROOT . '/migrations';
$migrations = scandir($migrationsDir);

$migrations = array_diff($migrations, ['.', '..', '.DS_Store']);
asort($migrations);

foreach ($migrations as $migration) {
  $name = preg_replace('/^[0-9]+-|\.migration/', '', $migration);
  $class = '\\Core\\Migrations\\' . $name;
  require_once($migrationsDir . '/' . $migration);
  $migration = new $class();
}
