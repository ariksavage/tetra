<?php
namespace Core\Base\Traits;

use \Core\Database\MySQL\Query\Select as selectQuery;
use \Core\Database\MySQL\Query\Insert as insertQuery;
use \Core\Database\MySQL\Query\Update as updateQuery;
use \Core\Database\MySQL\Query\Delete as deleteQuery;
use \Core\Users\Models\User;

trait Common {

  protected function permissionExists(string $dimension, string $action = ''): bool
  {
    $query = $this->select()->from('user_permissions')->where('dimension', '=', $dimension);
    if ($action) {
      $query->and('action', '=', $action);
    }
    $permission = $query->execute(true);
    if (!$permission) {
      if ($action){
        $title = "{$action} {$dimension}";
        $description = "User is allowed to " . ucwords(strtolower($action)) . " " . ucwords(strtolower($dimension));
      } else {
        $title = "{$dimension} ALL";
        $description = "User has full access to " . ucwords(strtolower($dimension));
        $action = "*";
      }
      $query = "INSERT INTO `user_permissions` (`dimension`, `action`, `title`, `description`)";
      $query .= " VALUES('{$dimension}', '{$action}', '{$title}', '{$description}')";
      if ($this->raw($query)->execute()) {
        return true;
      } else {
        return false;
      }
    } else {
      return true;
    }
  }

  /**
   * Check that the current user has the required permission
   *
   * @param  string $dimension Permission dimension
   * @param  string $action    Permission action (VIEW, UPDATE, DELETE)
   *
   * @return bool              Permission exists.
   *                           On failure, a 401 unauthorized error is thrown
   *                           and the current script is killed.
   */
  protected function requirePermission(string $dimension, string $action = ''): bool
  {
    $user = $this->getCurrentUser();
    if (!$user || !$user->hasPermission($dimension, $action)) {
      // ensure the permission exists
      $this->permissionExists($dimension, $action);
      $this->error("Permission Denied", 401);
      return false;
    }
    return true;
  }

  /**
   * Shorthand function to check permission
   * Uses the current object's dimension as default.
   * @param  string $action action being checked: VIEW, UPDATE, DELETE, etc
   * @return [type]         [description]
   */
  protected function reqPermission(string $action = ''): bool
  {
    $dimension = $this->dimension;
    return $this->requirePermission($dimension, $action);
  }
  /**
   * Shorthand function to get a config value
   * @param  string $key  Config key
   * @param  string $type Config type
   * @return Mixed        Config value
   */
  protected function configValue($key, $type = 'application')
  {
    $query = new selectQuery();
    $config = $query
      ->from('config')
      ->where('type', '=', $type)
      ->and('key', '=', $key)
      ->execute(true);
    if (!$config) {
      return NULL;
    }
    switch($config->value_type) {
      case 'int':
        return intval($config->value);
        break;
      case 'string':
        return strval($config->value);
        break;
      default:
        return $config->value;
        break;
    }
  }

    /**
   * Find the user's authorization token.
   *
   * First check session.
   * Then look in request headers (ie from API calls)
   *
   * @return String $authToken The Authorization Token.
   */
  protected function getAuthToken()
  {
    $authToken = '';
    if(isset($_SESSION['authorization_token'])) {
      return $_SESSION['authorization_token'];
    } else if (isset($_COOKIE['auth'])) {
      $_SESSION['authorization_token'] = $_COOKIE['auth'];
      return $_COOKIE['auth'];
    }
    if (function_exists('apache_request_headers')){
      $headers = \apache_request_headers();
      $authToken = $headers['Authorization'] ?? null;
    }
    if ($authToken){
      $authToken = str_replace('Bearer ', '', $authToken);
      $authToken = trim($authToken);
      $_SESSION['authorization_token'] = $authToken;
      return $authToken;
    }
    return false;
  }

  /**
   * Get the session's current user
   *
   * @return  User
   */
  public function getCurrentUser()
  {
    if (isset($_SESSION['user'])) {
      $user = $_SESSION['user'];
    } else {
      $user = new User();
      if ($user->byToken()) {
        $_SESSION['user'] = $user;
      } else {
        return FALSE;
      }
    }
    return $user;
  }

  public function sendEmail(string $to, string $subject, string $message)
  {
    $boundary = uniqid("boundary_", true);

    $from = $this->configValue('from_email');
    if (!$from) {
      $from = 'noreply@' .  $_SERVER['HTTP_HOST'];
    }

    $host = $_GET['host'];
    if (!$host){
      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
      $host = $protocol . "://" . $_SERVER['HTTP_HOST'];
    }

    $appName = $this->configValue('name');
    $bodyColor = '#ddd';
    $htmlMessage = [];
    $htmlMessage[] = '<!DOCTYPE html>';
    $htmlMessage[] = '<html>';
    $htmlMessage[] = '<head>';
    $htmlMessage[] = '<meta charset="UTF-8">';
    $htmlMessage[] = '<title>' . $subject . '</title>';
    $htmlMessage[] = '</head>';
    $htmlMessage[] = '<body style="margin:0; padding:0; background-color:' . $bodyColor . ';">';
    $htmlMessage[] = '<table width="100%" bgcolor="' . $bodyColor . '" cellpadding="0" cellspacing="0">';
    $htmlMessage[] = '<tr>';
    $htmlMessage[] = '<td align="center">';
    $htmlMessage[] = '<table width="600" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="margin: 20px 0 60px; padding: 20px; border-radius: 6px; font-family: Arial, sans-serif; text-align: left;">';
    $htmlMessage[] = '<tr>';
    $htmlMessage[] = '<td>';
    $htmlMessage[] = '<a href="'.$host.'"><img style="height: 60px;margin-right: 40px;" src="' . $host . '/public/KD-min.svg"/></a>';
    $htmlMessage[] = '</td>';
    $htmlMessage[] = '<td>';
    $htmlMessage[] = '<h1 style="margin:0;">' . $appName . '</h1>';
    $htmlMessage[] = '</td>';
    $htmlMessage[] = '</tr>';
    $htmlMessage[] = '<tr>';
    $htmlMessage[] = '<td colspan="2">';
    $htmlMessage[] = trim($message);
    $htmlMessage[] = '</td>';
    $htmlMessage[] = '</tr>';
    $htmlMessage[] = '</table>';
    $htmlMessage[] = '</td>';
    $htmlMessage[] = '</tr>';
    $htmlMessage[] = '</table>';
    $htmlMessage[] = '</body>';
    $htmlMessage[] = '</html>';
    $htmlBody = implode("\r\n", $htmlMessage);
    $plainText = strip_tags($htmlBody);
    // Always set content-type when sending HTML email
    $headers  = "MIME-Version: 1.0" . "\r\n";

    // Additional headers
    $headers .= 'From: ' . $from . "\r\n";
    $headers .= 'Reply-To: ' . $from . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";

    // Multipart body
    $body  = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $body .= $plainText . "\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $body .= $htmlBody . "\r\n";
    $body .= "--$boundary--";

    if (mail($to, $subject, $body, $headers)) {
      // echo "HTML email sent successfully.";
      return TRUE;
    } else {
      // echo "Failed to send HTML email.";
      return FALSE;
    }
  }

  /**
  * Convert kebab case URL to prettier camelCase for functions
  * eg. course-enrollments => courseEnrollments
  */
  public function toCamelCase($string, $capitalizeFirstCharacter = false)
  {
    $str = str_replace('-', ' ', $string);
    $str = ucwords($str);
    $str = str_replace(' ', '', $str);
    if (!$capitalizeFirstCharacter) {
        $str = lcfirst($str);
    }
    return $str;
  }
  protected function timestamp(string|int $date = 'now') {
    if (is_string($date)) {
      $date = strtotime($date);
    }
    return date('Y-m-d h:i:s', $date);
  }

  public function baseUrl(){
    return sprintf(
      "%s://%s",
      isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
      $_SERVER['SERVER_NAME']
    );
  }
}
?> 
