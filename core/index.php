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

require_once(CORE_ROOT . '/functions.php');
require_once(CORE_ROOT . '/models/user.model');

use \Tetra\Models\User;


// Globals

// Get postdata from either Angular or plain $_POST
$postdata = null;

if ($_POST) {
  $postdata = (object) $_POST;
} else {
  $rawdata = file_get_contents("php://input");
  $postdata = json_decode($rawdata);
}

$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['apitype'] ?? 'error';
unset($_GET['apitype']);
$action = $_GET['action'] ?? 'index';
unset($_GET['action']);
$action = dashesToCamelCase($action);

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
  http_response_code(404);
  die("$type is not a valid type");
}

$class = __NAMESPACE__ . '\\API\\' . dashesToCamelCase($type);
if (class_exists($class)) {
  $core = new $class();
} else {
  die("$class does not exist.");
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
  http_response_code(404);
  die("$type/$action is not a valid $method action");
}
