<?php
namespace Core;
/**
* Get current user,
* Define constants
*/

// All back end time is used / stored in UTC.
// On the front end, it can be converted to local time.
date_default_timezone_set('UTC');

define('CORE_ROOT', __dir__);
define('SERVER_ROOT', realpath(CORE_ROOT . '/../../'));
define('CONFIG_PATH', __dir__ . '/../../config');

require_once(CORE_ROOT . '/api/base.api');
require_once(CORE_ROOT . '/api/core.api');
