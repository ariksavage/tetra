<?php
/**
 * Page renderer
 *
 * Every request that isn't for the API or an existing file comes here (see
 * .htaccess and index.php): the first page a visitor or crawler loads. It
 * sends the app's built index.html with the page's head filled in on the
 * server, so a crawler that doesn't run JavaScript still gets the title,
 * description and metadata. Angular takes over in the browser as before.
 *
 * It never uses a session or login: what it writes is what anyone may see.
 *
 * PHP version 8.4
 *
 * @category   Helper
 * @package    Tetra
 * @author     Arik Savage <ariksavage@gmail.com>
 * @since      2026-11-13
 */

namespace Core\Pages\Helpers;

use Core\Config\Helpers\TetraConfig;

class PageRenderer
{
  use \Core\Base\Traits\Common;
  use \Core\Database\MySQL\Traits\Queries;

  /** The app's built page shell: index.html beside this core/ in a deploy. */
  public function shellPath(): string
  {
    return CORE_ROOT . '/index.html';
  }

  /**
   * Send the page for a path, eg. "/recipes/mac-n-cheese".
   */
  public function respond(string $path): never
  {
    $shell = is_file($this->shellPath()) ? file_get_contents($this->shellPath()) : NULL;
    if ($shell === NULL) {
      // A checkout that hasn't been built (eg. local development, where
      // ng serve sends the pages): nothing to fill in.
      $this->send(503, '<!doctype html><title>Not built</title><p>This page needs the app\'s build (index.html beside tetra/core).</p>');
    }
    $head = $this->head($path);
    $this->send(200, $this->fill($shell, $head));
  }

  /**
   * The head for a path. For now, the site's name as the title; the route
   * map and the models' metadata fill in the rest.
   *
   * @return object {title}
   */
  protected function head(string $path): object
  {
    return (object) [
      'title' => $this->siteName(),
    ];
  }

  /** The site's name: this site's setting, else tetra.json, else Tetra's default. */
  protected function siteName(): string
  {
    try {
      return strval($this->configValue('name') ?? TetraConfig::get('app.name'));
    } catch (\Throwable $e) {
      // No database (eg. before install): the app's name from tetra.json.
      return strval(TetraConfig::get('app.name'));
    }
  }

  /** The shell with its <title> replaced; values escaped for HTML. */
  protected function fill(string $shell, object $head): string
  {
    $title = '<title>' . self::escape($head->title) . '</title>';
    return preg_replace('#<title>.*?</title>#is', $title, $shell, 1) ?? $shell;
  }

  public static function escape(string $value): string
  {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
  }

  protected function send(int $status, string $html): never
  {
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    // Like index.html: always checked, so a new deploy is picked up at once.
    header('Cache-Control: no-cache');
    echo $html;
    exit;
  }
}
