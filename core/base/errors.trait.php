<?php
namespace Core\Base\Traits;

trait Errors {

  /**
   * Kill the current script, and return as much useful JSON data as possible
   *
   * @param String $message Error text.
   * @param Integer $code Error code, header to be returned.
   * @param String $type Category of error for reporting.
   * @param Object $data Additional data to be included in the response.
   *
   * @return Void Print error response.
   */
  public function error($message, $code = 500, $type = null, $data = null)
  {
    global $errorReporting;
    $error = new \stdClass();
    $error->message = $message;
    $error->code = $code;
    $error->type = $type;
    if ($data) {
      $error->data = $data;
    }
    if ($error->type == null) {
      switch($code) {
        case 401:
          $error->type = 'Not Authorized';
          break;
        case 404:
          $error->type = 'Not Found';
          break;
        default:
          $error->type = \get_class($this);
          break;
      }
    }
    $e = new \Exception();
    $error->stack = explode("\n", $e->getTraceAsString());
    array_shift($error->stack);

    if ($errorReporting && $code != 404) {
      $errfile = '';
      $errline = 0;
      $errorReporting->handle($code, $message, $errfile, $errline);
    }

    if (php_sapi_name() === 'cli') { // Command line response
      echo PHP_EOL . "{$error->code} ERROR {$error->type}" . PHP_EOL . $error->message . PHP_EOL;
    } else { // HTTP response
      http_response_code(intval($error->code));
      header('Content-Type: application/json');
      die(json_encode($error));
    }
  }
}
