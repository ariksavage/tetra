<?php
namespace Core;

define('CORE_ROOT', __dir__);
define('SERVER_ROOT', realpath(CORE_ROOT . '/../../'));
define('CONFIG_PATH', __dir__ . '/../../config');

// All back end time is used / stored in UTC.
// On the front end, it can be converted to local time.
date_default_timezone_set('UTC');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
