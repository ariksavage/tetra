<?php


namespace Core;
require_once(__dir__ . '/bootstrap.php');

$core = new \Core\App\API\App();

// Globals

$method = $_SERVER['REQUEST_METHOD'];
if (isset($_GET['apitype'])){
  $type = $_GET['apitype'];
  $type = $core->toCamelCase($type);
  $type = ucfirst($type);
  unset($_GET['apitype']);
} else {
  $type = NULL;
}
if (isset($_GET['action'])){
  $action = $_GET['action'];
  $action = $core->toCamelCase($action);
  unset($_GET['action']);
} else {
  $action = 'index';
}

if (isset($_GET['id'])){
  $id  = $_GET['id'];
  unset($_GET['id']);
} else {
  $id = NULL;
}
if (isset($_GET['id2'])){
  $id2 = $_GET['id2'];
  unset($_GET['id2']);
} else {
  $id2 = NULL;
}
/**
 * Load the appropriate class of API to handle the request.
 */
$className = "\\Core\\{$type}\\API\\{$type}";

// The class loader throws when a class has no file: for a type that does not exist,
// that is a 404, not a server error.
try {
  $exists = $type && class_exists($className);
} catch (\Exception $e) {
  $exists = FALSE;
}

if ($exists) {
  $api = new $className();
} else {
  $core->error($type ? "$type is not a valid type" : '', 404);
}


/**
 * Call the API method as defined by the request type and action.
 */
$fn = "$action$method";
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
