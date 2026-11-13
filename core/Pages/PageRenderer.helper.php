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
   * The head for a path, from the route map (PageRoutes):
   *
   * - The site's name and description (tetra.json's seo.description) for
   *   every page; a public route may name its own title and description.
   * - robots: noindex for a URL no route matches or a route that isn't
   *   public (nothing is exposed unless a route says so), and noindex,
   *   nofollow everywhere when tetra.json's seo.indexable is false.
   *
   * @return object {title, description, robots, match}
   */
  protected function head(string $path): object
  {
    $match = PageRoutes::match($path);
    $public = PageRoutes::isPublic($match);
    $site = $this->siteName();
    $head = (object) [
      'title' => $site,
      'description' => strval(TetraConfig::get('seo.description')),
      'robots' => NULL,
      'match' => $match,
    ];
    if (!TetraConfig::get('seo.indexable')) {
      $head->robots = 'noindex, nofollow';
    } elseif (!$public) {
      $head->robots = 'noindex';
    }
    if ($public) {
      if (!empty($match->route->title)) {
        $head->title = "{$match->route->title} | {$site}";
      }
      if (!empty($match->route->description)) {
        $head->description = $match->route->description;
      }
    }
    return $head;
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

  /** The shell with its <title> replaced and the head's tags added; values escaped for HTML. */
  protected function fill(string $shell, object $head): string
  {
    $title = '<title>' . self::escape($head->title) . '</title>';
    $html = preg_replace('#<title>.*?</title>#is', $title, $shell, 1) ?? $shell;
    $tags = [];
    if ($head->description !== '') {
      $tags[] = '<meta name="description" content="' . self::escape($head->description) . '">';
    }
    if ($head->robots) {
      $tags[] = '<meta name="robots" content="' . self::escape($head->robots) . '">';
    }
    if (!$tags) {
      return $html;
    }
    // Just before </head>, after the shell's own tags.
    $insert = '  ' . implode("\n  ", $tags) . "\n";
    $pos = stripos($html, '</head>');
    return $pos === FALSE ? $html : substr($html, 0, $pos) . $insert . substr($html, $pos);
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
