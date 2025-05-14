<?php
namespace Core;

date_default_timezone_set('UTC');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once(__dir__ . '/definitions.php');

require_once(CORE_ROOT . '/ClassLoader.php');
$loader = new ClassLoader();

if (file_exists(SERVER_ROOT . '/vendor/autoload.php')) {
  require_once(SERVER_ROOT . '/vendor/autoload.php');
}


// require_once(CORE_ROOT . '/error.trait');
// require_once(CORE_ROOT . '/interfaces/queries/queries.trait');
// require_once(CORE_ROOT . '/core.trait');
// require_once(CORE_ROOT . '/interfaces/db.php');

