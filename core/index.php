<?php
namespace Core;
require_once(__dir__ . '/bootstrap.php');

if (file_exists(SERVER_ROOT . '/vendor/autoload.php')) {
  require_once(SERVER_ROOT . '/vendor/autoload.php');
}

$pluginsDir = realpath(SERVER_ROOT . '/plugins');

if (file_exists($pluginsDir . '/loader.php')) {
  require_once($pluginsDir.'/loader.php');
}

// All back end time is used / stored in UTC.
// On the front end, it can be converted to local time.
date_default_timezone_set('UTC');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$core = new \Core\API\App();

if (file_exists($pluginsDir. '/error/errorReporting.php')) {
  require_once($pluginsDir. '/error/errorReporting.php');
  $errorReporting = new \Core\ErrorReporting();
}

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

/**
 * Find core plugin, if it exists
 */
if (file_exists(CORE_ROOT . "/api/$type.api")) {
  require_once CORE_ROOT . "/api/$type.api";
  $APIclass = __NAMESPACE__ . '\\API\\' . $core->toCamelCase($type);
}
/**
 * If a corresponding $type plugin exists, load it first.
 */

if (is_dir($pluginsDir)) {
  $pluginDir = $pluginsDir . "/{$type}";
  if (is_dir($pluginDir)) {
    $files = $images = glob("{$pluginDir}/*.api", GLOB_BRACE);
    if (count($files) == 1) {
      $pluginAPIfile = reset($files);

      require_once $pluginAPIfile;
      $name = str_replace('.api', '', basename($pluginAPIfile));
      $APIclass = __NAMESPACE__ . '\\API\\' . ucfirst($name);
    }
  }
} else {
  mkdir($pluginsDir);
}

/**
 * Instatiate the API class.
 */
if ($APIclass && class_exists($APIclass)) {
  $api = new $APIclass();
} else {
  $core->error("$type is not a valid type", 404);
}

/**
 * Call the API method as defined by the request type and action.
 */
$fn = "$action$method";
$errorReporting->breadcrumb('API Method ' . $fn);
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
