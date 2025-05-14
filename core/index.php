<?php
namespace Core;
require_once(__dir__ . '/bootstrap.php');



// All back end time is used / stored in UTC.
// On the front end, it can be converted to local time.


$core = new \Core\App\API\App();
// die();
// if (file_exists($pluginsDir. '/error/errorReporting.php')) {
// $errorReporting = new \Core\ErrorReporting();
// }

// Globals

$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['apitype'] ?? 'error';
$type = $core->toCamelCase($type);
unset($_GET['apitype']);
$action = $_GET['action'] ?? 'index';
$action = $core->toCamelCase($action);
unset($_GET['action']);


$id  = $_GET['id']  ?? NULL;
unset($_GET['id']);
$id2 = $_GET['id2'] ?? NULL;
unset($_GET['id2']);

/**
 * Load the appropriate class of API to handle the request.
 */

$APIclass = '';

$type = $core->toCamelCase($type);
$className = "\\Core\\{$type}\\API\\{$type}";

if (class_exists($className)) {
  $api = new $className();
} else {
  $core->error("$className is not a valid type", 404);
}

/**
 * Instatiate the API class.
 */
// if ($APIclass && class_exists($APIclass)) {
//   $api = new $APIclass();
// } else {
//   $core->error("$APIclass is not a valid type", 404);
// }

/**
 * Call the API method as defined by the request type and action.
 */
$fn = "$action$method";
if (isset($errorReporting)) {
  $errorReporting->breadcrumb('API Method ' . $fn);
}
if (method_exists($api, $fn)) {
  if ($id && $id2) {
    $api->$fn($id, $id2);
  } else if ($id) {
    $api->$fn($id);
  } else {
    $api->$fn();
  }
} else {
  $core->error("$type/$action is not a valid $method action", 404);
}
