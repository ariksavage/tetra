<?php
namespace Core\Base\Traits;

use \Core\Database\MySQL\Query\Select as selectQuery;
use \Core\Database\MySQL\Query\Insert as insertQuery;
use \Core\Database\MySQL\Query\Update as updateQuery;
use \Core\Database\MySQL\Query\Delete as deleteQuery;
use \Core\Users\Models\User;

trait Common {

  protected function permissionIsPublic(string $dimension, string $action = ''): bool
  {
    $permissionQuery = $this->select()->from('permissions')->where('dimension', '=', $dimension);
    if ($action) {
      $permissionQuery->and('action', '=', $action);
    }
    $permission = $permissionQuery->execute(TRUE);
    $publicQuery = $this->select()->from('roles_permissions')
      ->where('permission_id', '=', $permission->id)
      ->and('role_id', '=', 0);
    $permission = $publicQuery->execute(TRUE);
    return !!$permission;
  }

  protected function permissionExists(string $dimension, string $action = ''): bool
  {
    $query = $this->select()->from('permissions')->where('dimension', '=', $dimension);
    if ($action) {
      $query->and('action', '=', $action);
    }
    $permission = $query->execute(TRUE);
    if (!$permission) {
      if ($action) {
        $title = "{$action} {$dimension}";
        $description = "User is allowed to " . ucwords(strtolower($action)) . " " . ucwords(strtolower($dimension));
      } else {
        $title = "{$dimension} ALL";
        $description = "User has full access to " . ucwords(strtolower($dimension));
        $action = "*";
      }
      $query = "INSERT INTO `permissions` (`dimension`, `action`, `title`, `description`)";
      $query .= " VALUES('{$dimension}', '{$action}', '{$title}', '{$description}')";
      if ($this->raw($query)->execute()) {
        return TRUE;
      } else {
        return FALSE;
      }
    } else {
      return TRUE;
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
    if(!$user) {
      if ($this->permissionIsPublic($dimension, $action)) {
        return TRUE;
      } else {
        $this->error("Permission Denied", 401);
      }
    } else if ( !$user->hasPermission($dimension, $action)) {
      // ensure the permission exists
      $this->permissionExists($dimension, $action);
      $this->error("Permission Denied", 401);
      return FALSE;
    }
    return TRUE;
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
   * Add a Server-Timing header saying how long the server spent on this request
   * and how much of that was database queries. Browsers show it in the network
   * panel, so slow responses can be told apart from a slow connection.
   *
   * @return void
   */
  protected function sendServerTiming(): void
  {
    if (headers_sent() || php_sapi_name() === 'cli') {
      return;
    }
    $started = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(TRUE);
    $app = (microtime(TRUE) - $started) * 1000;
    $db = \Core\Database\MySQL\DB\MySQL::$queryTime * 1000;
    $queries = \Core\Database\MySQL\DB\MySQL::$queryCount;
    header(sprintf('Server-Timing: app;dur=%.1f, db;dur=%.1f;desc="%d queries"', $app, $db, $queries));
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
      ->execute(TRUE);
    if (!$config) {
      return NULL;
    }
    switch ($config->value_type) {
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
    if (isset($_SESSION['authorization_token'])) {
      return $_SESSION['authorization_token'];
    }
    // The header first: it's what the app sends for its own login. A cookie
    // can belong to another app on the same host (cookies ignore the port).
    $authToken = '';
    if (function_exists('apache_request_headers')) {
      $headers = \apache_request_headers();
      $authToken = $headers['Authorization'] ?? '';
    }
    if ($authToken) {
      $authToken = trim(str_replace('Bearer ', '', $authToken));
    } elseif (isset($_COOKIE['auth'])) {
      $authToken = $_COOKIE['auth'];
    }
    if ($authToken) {
      $_SESSION['authorization_token'] = $authToken;
      return $authToken;
    }
    return FALSE;
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

  /**
   * Send an HTML email (with a plain-text copy), wrapped in the app's layout.
   *
   * @param string $to      Recipient address, or several separated by commas.
   * @param string $subject Subject line.
   * @param string $message HTML body.
   * @param array  $options Optional:
   *   - cc:          address or list of addresses to copy
   *   - reply_to:    where replies go (default: the From address)
   *   - from:        sender address (default: the from_email setting)
   *   - from_name:   sender name shown in mail clients
   *   - attachments: list of ['filename' => ..., 'content' => raw bytes, 'type' => MIME type]
   *   - plain:       TRUE to send the message as written, without the app's layout
   *
   * @return bool TRUE if the mail system accepted it.
   */
  public function sendEmail(string $to, string $subject, string $message, array $options = []): bool
  {
    // Header values can't carry line breaks, or they could add headers.
    $clean = fn($value) => trim(preg_replace('/[\r\n]+/', ' ', strval($value)));
    $list = fn($value) => implode(', ', array_filter(array_map($clean, is_array($value) ? $value : [$value])));

    $from = $clean($options['from'] ?? $this->configValue('from_email') ?: 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $fromName = $clean($options['from_name'] ?? '');
    $fromHeader = $fromName ? mb_encode_mimeheader($fromName, 'UTF-8') . " <{$from}>" : $from;
    $replyTo = $list($options['reply_to'] ?? $from);
    $cc = $list($options['cc'] ?? []);

    $htmlBody = !empty($options['plain']) ? $this->plainEmailHtml($subject, $message) : $this->emailLayout($subject, $message);
    // The plain-text copy: a line per <br> and list item, a blank line per paragraph.
    $plainText = \Core\Base\Helpers\Html::toText($message);

    $headers = [
      'MIME-Version: 1.0',
      'From: ' . $fromHeader,
      'Reply-To: ' . $replyTo,
      'X-Mailer: PHP/' . phpversion(),
    ];
    if ($cc) {
      $headers[] = 'Cc: ' . $cc;
    }

    // The text and HTML versions, as alternatives.
    $alt = uniqid('alt_', TRUE);
    $alternatives  = "--{$alt}\r\n";
    $alternatives .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $alternatives .= chunk_split(base64_encode($plainText)) . "\r\n";
    $alternatives .= "--{$alt}\r\n";
    $alternatives .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $alternatives .= chunk_split(base64_encode($htmlBody)) . "\r\n";
    $alternatives .= "--{$alt}--\r\n";

    $attachments = $options['attachments'] ?? [];
    if (!$attachments) {
      $headers[] = "Content-Type: multipart/alternative; boundary=\"{$alt}\"";
      $body = $alternatives;
    } else {
      // Attachments: the message and each file as parts of a mixed message.
      $mixed = uniqid('mixed_', TRUE);
      $headers[] = "Content-Type: multipart/mixed; boundary=\"{$mixed}\"";
      $body  = "--{$mixed}\r\n";
      $body .= "Content-Type: multipart/alternative; boundary=\"{$alt}\"\r\n\r\n";
      $body .= $alternatives;
      foreach ($attachments as $file) {
        $name = str_replace('"', '', $clean($file['filename'] ?? 'attachment'));
        $type = $clean($file['type'] ?? 'application/octet-stream');
        $body .= "--{$mixed}\r\n";
        $body .= "Content-Type: {$type}; name=\"{$name}\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"{$name}\"\r\n\r\n";
        $body .= chunk_split(base64_encode($file['content'] ?? '')) . "\r\n";
      }
      $body .= "--{$mixed}--";
    }

    return mail($list($to), mb_encode_mimeheader($clean($subject), 'UTF-8'), $body, implode("\r\n", $headers));
  }

  /**
   * The message as its own HTML document, with no layout around it.
   */
  protected function plainEmailHtml(string $subject, string $message): string
  {
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($subject) . '</title></head>'
      . '<body style="font-family: Arial, sans-serif; line-height: 1.5;">' . trim($message) . '</body></html>';
  }

  /**
   * The app's email layout: its logo and name above the message.
   */
  protected function emailLayout(string $subject, string $message): string
  {
    $host = $_GET['host'] ?? null;
    if (!$host) {
      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
      $host = $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
      if (stristr($host, 'localhost')){
        $host = 'http://localhost:4200';
      }
    }

    $appName = $this->configValue('name');
    $bodyColor = '#ddd';
    $htmlMessage = [];
    $htmlMessage[] = '<!DOCTYPE html>';
    $htmlMessage[] = '<html>';
    $htmlMessage[] = '<head>';
    $htmlMessage[] = '<meta charset="UTF-8">';
    $htmlMessage[] = '<title>' . htmlspecialchars($subject) . '</title>';
    $htmlMessage[] = '</head>';
    $htmlMessage[] = '<body style="margin:0; padding:0; background-color:' . $bodyColor . ';">';
    $htmlMessage[] = '<table width="100%" bgcolor="' . $bodyColor . '" cellpadding="0" cellspacing="0">';
    $htmlMessage[] = '<tr>';
    $htmlMessage[] = '<td align="center">';
    $bodyStyles = array(
      'margin: 20px 0 60px',
      'padding: 20px',
      'border-radius: 6px',
      'font-family: Arial, sans-serif',
      'text-align: left'
    );
    $bodyStyle = implode(';', $bodyStyles);
    $htmlMessage[] = '<table width="600" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="'.$bodyStyle.'">';
    $htmlMessage[] = '<tr>';
    $htmlMessage[] = '<td>';
    $logoSrc =  $host . '/logo.svg';
    $htmlMessage[] = '<a href="'.$host.'"><img style="height: 60px;margin-right: 40px;" src="' . $logoSrc . '"/></a>';
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
    return implode("\r\n", $htmlMessage);
  }

  /**
  * Convert kebab case URL to prettier camelCase for functions
  * eg. course-enrollments => courseEnrollments
  */
  public function toCamelCase($string, $capitalizeFirstCharacter = FALSE)
  {
    $str = str_replace('-', ' ', $string);
    $str = ucwords($str);
    $str = str_replace(' ', '', $str);
    if (!$capitalizeFirstCharacter) {
        $str = lcfirst($str);
    }
    return $str;
  }

  protected function timestamp(string|int $date = 'now')
  {
    if (is_string($date)) {
      $date = strtotime($date);
    }
    return date('Y-m-d H:i:s', $date);
  }

  public function baseUrl()
  {
    return sprintf(
      "%s://%s",
      isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
      $_SERVER['SERVER_NAME']
    );
  }
}
