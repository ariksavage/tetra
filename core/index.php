<?php
namespace Core;

require_once(__dir__ . '/bootstrap.php');

$core = new \Core\API\Core();

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

/**
 * Load the appropriate class of API to handle the request.
 */
$APIclass = '';
/**
 * If a corresponding $type plugin exists, load it first.
 */
$pluginsDir = realpath(SERVER_ROOT . '/plugins');
if (is_dir($pluginsDir)) {
  $pluginDir = $pluginsDir . "/{$type}";
  if (is_dir($pluginDir)) {
    $files = $images = glob("{$pluginDir}/*.api", GLOB_BRACE);
    if (count($files) == 1) {
      $pluginAPIfile = reset($files);

      require_once $pluginAPIfile;
      $name = str_replace('.api', '', basename($pluginAPIfile));
      $APIclass = __NAMESPACE__ . '\\API\\' . $name;
    }
  }
} else {
  mkdir($pluginsDir);
}

/**
 * If no plugin, load from core.
 */
if (!$APIclass) {
  if (file_exists(CORE_ROOT . "/api/$type.api")) {
    require_once CORE_ROOT . "/api/$type.api";
    $APIclass = __NAMESPACE__ . '\\API\\' . $core->toCamelCase($type);
  } else {
    $core->error("$type is not a valid type", 404);
  }
}

/**
 * Instatiate the API class.
 */
if ($APIclass && class_exists($APIclass)) {
  $api = new $APIclass();
} else {
  $core->error("{$APIclass} not found.", 404);
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
