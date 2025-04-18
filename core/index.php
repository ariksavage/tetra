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

$bugsnagConfig = CONFIG_PATH . '/bugsnag.config';
$bugsnagCfg = (object) \yaml_parse_file($bugsnagConfig, 0);

$bugsnag = \Bugsnag\Client::make($bugsnagCfg->apiKey);
$bugsnag->setAppType($bugsnagCfg->appType);
$bugsnag->setAppVersion($bugsnagCfg->appVersion);
\Bugsnag\Handler::register($bugsnag);
$bugsnag->setReleaseStage($bugsnagCfg->stage);

// $bugsnag->notifyException(new \RuntimeException("Test PHP error"));

$bugsnag->registerCallback(function ($report) {
  global $core;
  $user = $core->getCurrentUser();
    if ($user) {
      $report->setMetaData([
          'account' => [
              'id' => $user->id,
              'name' => $user->name(),
          ]
      ]);
    }
});

// error handler function
function errorHandler($errno, $errstr, $errfile, $errline)
{
  global $bugsnag;
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting, so let it fall
        // through to the standard PHP error handler
        return false;
    }

    // $errstr may need to be escaped:
    $errstr = htmlspecialchars($errstr);
    $exit = false;

    switch ($errno) {
      case E_USER_ERROR:
        $err  = "<b>My ERROR</b> [$errno] $errstr<br />\n";
        $err .= "  Fatal error on line $errline in file $errfile";
        $err .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
        $err .= "Aborting...<br />\n";
        echo $err;

        $exit = 1;
        break;

      case E_USER_WARNING:
        $err = "<b>My WARNING</b> [$errno] $errstr<br />\n";
        break;

      case E_USER_NOTICE:
        $err = "<b>My NOTICE</b> [$errno] $errstr<br />\n";
        break;

      default:
        $err = "Unknown error type: [$errno] $errstr<br />\n";
        break;
    }
    echo $err;
    $bugsnag->notifyException(new \Exception($err));
    if ($exit) {
      exit($exit);
    }
    /* Don't execute PHP internal error handler */
    return true;
}

// set to the user defined error handler
$old_error_handler = set_error_handler('\Core\errorHandler');

// Globals

$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['apitype'] ?? 'error';
unset($_GET['apitype']);
$action = $_GET['action'] ?? 'index';
unset($_GET['action']);
$action = $core->toCamelCase($action);

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
      $APIclass = __NAMESPACE__ . '\\API\\' . $name;
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
$bugsnag->leaveBreadcrumb('API Method ' . $fn);
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
