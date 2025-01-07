<?php

namespace Tetra;
/**
* convert kebab case URL to prettier camelCase for functions
* eg. course-enrollments => courseEnrollments
*/
function dashesToCamelCase($string, $capitalizeFirstCharacter = false) 
{
    $str = str_replace('-', '', ucwords($string, '-'));
    if (!$capitalizeFirstCharacter) {
        $str = lcfirst($str);
    }
    return $str;
}

/**
 * Kill the current script, and return as much useful JSON data as possible
 * 
 * @param String $message Error text.
 * @param String $type Category of error for reporting.
 * @param Integer $code Error code, header to be returned.
 * @param Object $data Additional data to be included in the response.
 * 
 * @return JSON data.
 */
function error($message = null, $type = null, $code = 500, $data = null) {
  if ($message) {
    $response = new \stdClass();
    $response->error = new \stdClass();
    if ($data){
      $response->error = (object) array_merge((array) $response->error, (array) $data);
    }
    $response->error->message = $message;
    $response->error->type = $type;
    $response->error->code = $code;
    $e = new \Exception;
    $response->error->stack = $e->getTraceAsString();
    $response->error->stack = explode("\n", $response->error->stack);
    http_response_code($code);
    switch($code){
      case 401:
        header('HTTP/1.0 401 Unauthorized');
        break;
      case 404:
        header("HTTP/1.0 404 Not Found");
        break;
      default:
        header($_SERVER['SERVER_PROTOCOL'] . "{$code} Internal Server Error", true, $code);
        break;
    }
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode($response));
  }
}
