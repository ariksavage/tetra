<?php

namespace Tetra;
/**
* Get current user,
* Define constants
*/

// All back end time is used / stored in UTC.
// On the front end, it can be converted to local time.
date_default_timezone_set('UTC');

define('CORE_ROOT', __dir__);
define('SERVER_ROOT', realpath(CORE_ROOT . '/../'));
define('CONFIG_PATH', __dir__ . '/../../config');

require_once(CORE_ROOT . '/api/base.api');
require_once(CORE_ROOT . '/models/user.model');

use \Tetra\Models\User;

$core = new \Tetra\API\Base();

// Globals

$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['apitype'] ?? 'error';
unset($_GET['apitype']);
$action = $_GET['action'] ?? 'index';
unset($_GET['action']);
$action = $core->toCamelCase($action);

$id  = $_GET['id']  ?? null;
unset($_GET['id']);
$id2 = $_GET['id2'] ?? null;
unset($_GET['id2']);

// Check authorization

$currentUser = new User();

// $skipLogin = ($type == 'users' && $action == 'login') || ($action == 'tetra');
// var_dump($skipLogin) {

// }
// if (!$skipLogin && !$currentUser->byToken()) {
//   \Tetra\error("Not authorized", "Core", 401);
// }

// Load override classes first, then look to core
$moduleClassFile = "/core/modules/$type.api";
if (file_exists($moduleClassFile)) {
  require_once $moduleClassFile;
} else if (file_exists(CORE_ROOT . "/api/$type.api")) {
  require_once CORE_ROOT . "/api/$type.api";
} else {
  $core->error("$type is not a valid type", 404);
}

$class = __NAMESPACE__ . '\\API\\' . $core->toCamelCase($type);
if (class_exists($class)) {
  $core = new $class();
} else {
  $core->error("$class does not found.", 404);
}

$fn = "$action$method";
if (method_exists($core, $fn)) {
  if ($id && $id2) {
    $core->$fn($id, $id2);
  } else if ($id) {
    $core->$fn($id);
  } else {
    $core->$fn();
  }
} else {
  $core->error("$type/$action is not a valid $method action", 404);
}
