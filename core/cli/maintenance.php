<?php

namespace kdCore;

require_once(__dir__ . '/../bootstrap.php');
require_once CORE_ROOT . '/models/migration.model';

// look for migrations and run them if not manual

$migrations_dir = SERVER_ROOT . '/migrations';
$migrations = scandir($migrations_dir);

$migrations = array_diff($migrations, ['.', '..', '.DS_Store']);
asort($migrations);


foreach ($migrations as $migration) {
  $name = preg_replace('/^[0-9]+-|\.migration/', '', $migration);
  $class = '\\Core\\Migrations\\' . $name;
  require_once($migrations_dir . '/' . $migration);
  $migration = new $class();
}
