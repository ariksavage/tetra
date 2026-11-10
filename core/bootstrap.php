<?php
namespace Core;

// All back end time is used / stored in UTC.
// On the front end, it can be converted to local time.
date_default_timezone_set('UTC');

\mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once(__dir__ . '/definitions.php');

require_once(CORE_ROOT . '/classLoader.php');
$loader = new ClassLoader();

if (file_exists(SERVER_ROOT . '/vendor/autoload.php')) {
  require_once(SERVER_ROOT . '/vendor/autoload.php');
}