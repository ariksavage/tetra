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
    // robots.txt and sitemap.xml are generated, never files (see Discovery).
    if ($path === '/robots.txt' || $path === '/sitemap.xml') {
      $this->discovery($path);
    }
    $shell = is_file($this->shellPath()) ? file_get_contents($this->shellPath()) : NULL;
    if ($shell === NULL) {
      // A checkout that hasn't been built (eg. local development, where
      // ng serve sends the pages): nothing to fill in.
      $this->send(503, '<!doctype html><title>Not built</title><p>This page needs the app\'s build (index.html beside tetra/core).</p>');
    }
    $head = $this->head($path);
    // A record's page changed when the record did, or the build (the shell).
    $lastModified = $head->modified ? max($head->modified, intval(filemtime($this->shellPath()))) : NULL;
    $this->send($head->status, $this->fill($shell, $head), 'text/html; charset=utf-8', 'no-cache', $lastModified);
  }

  /**
   * The head for a path, from the route map (PageRoutes):
   *
   * - Every page: the site's name and description (tetra.json's
   *   seo.description), canonical URL, Open Graph and Twitter tags.
   * - robots: noindex for a URL no route matches or a route that isn't
   *   public (nothing is exposed unless a route says so); noindex, nofollow
   *   everywhere when tetra.json's seo.indexable is false.
   * - A public route with a model: the record found by the route's key, and
   *   its mapper's title, description, image, type and JSON-LD; a 404 with
   *   noindex when there's no such record.
   * - A public route without a model: its own title and description from
   *   the route map. The home page also gets the site's WebSite JSON-LD.
   *
   * @return object {status, title, description, image, type, canonical, robots, jsonLd, match}
   */
  protected function head(string $path): object
  {
    $match = PageRoutes::match($path);
    $public = PageRoutes::isPublic($match);
    $site = $this->siteName();
    $head = (object) [
      'status' => 200,
      'site' => $site,
      'title' => $site,
      'description' => strval(TetraConfig::get('seo.description')),
      'image' => strval(TetraConfig::get('seo.image')),
      'type' => 'website',
      'canonical' => $path,
      'robots' => $public ? NULL : 'noindex',
      'jsonLd' => NULL,
      'content' => '',
      'path' => $path,
      'modified' => NULL,
      'match' => $match,
    ];

    if ($public && !empty($match->route->model)) {
      $model = $this->load($match);
      if (!$model) {
        $head->status = 404;
        $head->title = "Not found | {$site}";
        $head->robots = 'noindex';
      } else {
        $mapper = $this->mapper($match->route);
        $meta = $mapper->meta($model);
        $meta->content = $meta->content !== '' ? $meta->content : $mapper->content($model);
        $this->apply($head, $meta, $path);
        $modified = $model->date_modified ?? NULL;
        $head->modified = is_numeric($modified) ? intval($modified) : (strtotime(strval($modified)) ?: NULL);
      }
    } elseif ($public && !empty($match->route->page) && ($meta = $this->pageMeta($match->route)) !== NULL) {
      // A public page that isn't one model (eg. a list): the mapper's page method.
      $this->apply($head, $meta, $path);
    } elseif ($public) {
      if (!empty($match->route->title)) {
        $head->title = "{$match->route->title} | {$site}";
      }
      if (!empty($match->route->description)) {
        $head->description = $match->route->description;
      }
      if ($match->route->path === '/') {
        $head->jsonLd = array_filter([
          '@context' => 'https://schema.org',
          '@type' => 'WebSite',
          'name' => $site,
          'url' => $this->absolute('/'),
          'description' => $head->description,
        ]);
      }
    }

    if (!TetraConfig::get('seo.indexable')) {
      $head->robots = 'noindex, nofollow';
    }
    return $head;
  }

  /** The site's discovery files: Discovery, on this request's site address. */
  public function discoveryFor(): Discovery
  {
    return new Discovery(fn(string $path) => $this->absolute($path));
  }

  protected function discovery(string $path): never
  {
    $discovery = $this->discoveryFor();
    if ($path === '/robots.txt') {
      $this->send(200, $discovery->robotsTxt(), 'text/plain; charset=utf-8', 'max-age=3600');
    }
    $xml = $discovery->sitemapXml();
    if ($xml === NULL) {
      $this->send(404, "This site isn't indexed.\n", 'text/plain; charset=utf-8', 'max-age=3600');
    }
    // The sitemap changed when its newest page did.
    $this->send(200, $xml, 'application/xml; charset=utf-8', 'max-age=3600', $discovery->lastModified);
  }

  /** Lay a mapper's PageMeta over the page's defaults. */
  protected function apply(object $head, PageMeta $meta, string $path): void
  {
    $head->title = $meta->title !== '' ? "{$meta->title} | {$head->site}" : $head->site;
    $head->description = $meta->description !== '' ? $meta->description : $head->description;
    $head->image = $meta->image !== '' ? $meta->image : $head->image;
    $head->type = $meta->type;
    $head->canonical = $meta->canonical !== '' ? $meta->canonical : $path;
    $head->jsonLd = $meta->jsonLd;
    $head->content = $meta->content;
  }

  /**
   * A public page that isn't one model: the route's "page" names a method on
   * its mapper ("schema") that returns the page's PageMeta, eg. a list.
   */
  protected function pageMeta(object $route): ?PageMeta
  {
    $mapper = $this->mapper($route);
    $method = strval($route->page);
    if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $method) || !method_exists($mapper, $method)) {
      error_log("Page renderer: the mapper for {$route->path} has no page method {$method}");
      return NULL;
    }
    try {
      $meta = $mapper->$method();
      return $meta instanceof PageMeta ? $meta : NULL;
    } catch (\Throwable $e) {
      error_log("Page renderer: {$method} failed for {$route->path}: " . $e->getMessage());
      return NULL;
    }
  }

  /**
   * The record a public route shows: its model class (under Core), found by
   * the route's key (default id) from the path's :name segment. NULL when
   * there's no such record, or the route names a class that isn't a model.
   */
  protected function load(object $match): ?object
  {
    $class = '\\Core\\' . ltrim($match->route->model, '\\');
    $key = strval($match->route->key ?? 'id');
    $params = (array) $match->params;
    $value = reset($params);
    if ($value === FALSE || !preg_match('/^[a-z_][a-z0-9_]*$/i', $key)) {
      return NULL;
    }
    try {
      if (!class_exists($class) || !is_subclass_of($class, '\\Core\\Base\\Models\\Base')) {
        return NULL;
      }
      $model = new $class();
      if (!property_exists($model, $key)) {
        return NULL;
      }
      return $model->byProperty($key, $value, FALSE) ?: NULL;
    } catch (\Throwable $e) {
      error_log("Page renderer: couldn't load {$class} by {$key}: " . $e->getMessage());
      return NULL;
    }
  }

  /** The route's mapper (its "schema", a class under Core), or the default. */
  protected function mapper(object $route): SchemaMapper
  {
    if (!empty($route->schema)) {
      $class = '\\Core\\' . ltrim($route->schema, '\\');
      try {
        if (class_exists($class) && is_subclass_of($class, SchemaMapper::class)) {
          return new $class();
        }
      } catch (\Throwable $e) {
        // The class loader throws for a class with no file.
      }
      error_log("Page renderer: {$route->schema} isn't a SchemaMapper; using the default");
    }
    return new DefaultMapper();
  }

  /**
   * A full URL for a path on this site (or a URL as it is), on the site's
   * address (seo.baseUrl: this site's setting, else tetra.json), or the
   * request's own address when there's none.
   */
  public function absolute(string $pathOrUrl): string
  {
    if ($pathOrUrl === '' || preg_match('#^https?://#i', $pathOrUrl)) {
      return $pathOrUrl;
    }
    try {
      $base = strval($this->configValue('base_url') ?? '');
    } catch (\Throwable $e) {
      $base = strval(TetraConfig::get('seo.baseUrl'));
    }
    if (preg_match('#^https?://[^/\s]+$#i', $base)) {
      return $base . '/' . ltrim($pathOrUrl, '/');
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $host = preg_replace('/[^a-z0-9.:\-]/i', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    return ($https ? 'https' : 'http') . '://' . $host . '/' . ltrim($pathOrUrl, '/');
  }

  /**
   * The JSON-LD with its links made full URLs: values of url, image, @id and
   * item that are paths from the site's root ("/recipes/..."), at any depth.
   * A mapper writes paths; the site's address is the renderer's business.
   * Prefixed keys (an app's own terms, eg. thyme:recipe) are left exactly as
   * they are.
   */
  protected function absoluteLinks(mixed $value, string $key = ''): mixed
  {
    if (str_contains($key, ':')) {
      return $value;
    }
    if (is_string($value)) {
      return in_array($key, ['url', 'image', '@id', 'item'], TRUE) && str_starts_with($value, '/') ? $this->absolute($value) : $value;
    }
    if (is_array($value)) {
      $out = [];
      foreach ($value as $k => $v) {
        // A list (eg. image: [...]) keeps its parent's key.
        $out[$k] = $this->absoluteLinks($v, is_int($k) ? $key : strval($k));
      }
      return $out;
    }
    return $value;
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

  /**
   * The shell with its <title> replaced and the page's tags added before
   * </head>. Every value is escaped for HTML; the JSON-LD is encoded so it
   * can't close its script tag.
   */
  protected function fill(string $shell, object $head): string
  {
    $e = fn($v) => self::escape(strval($v));
    $html = preg_replace('#<title>.*?</title>#is', '<title>' . $e($head->title) . '</title>', $shell, 1) ?? $shell;
    $url = $this->absolute($head->canonical);
    $image = $this->absolute($head->image);
    $tags = [];
    if ($head->description !== '') {
      $tags[] = '<meta name="description" content="' . $e($head->description) . '">';
    }
    if ($head->robots) {
      $tags[] = '<meta name="robots" content="' . $e($head->robots) . '">';
    }
    if ($head->status === 200) {
      $tags[] = '<link rel="canonical" href="' . $e($url) . '">';
      $tags[] = '<meta property="og:type" content="' . $e($head->type) . '">';
      $tags[] = '<meta property="og:site_name" content="' . $e($head->site) . '">';
      $tags[] = '<meta property="og:title" content="' . $e($head->title) . '">';
      $tags[] = '<meta property="og:url" content="' . $e($url) . '">';
      if ($head->description !== '') {
        $tags[] = '<meta property="og:description" content="' . $e($head->description) . '">';
      }
      if ($image !== '') {
        $tags[] = '<meta property="og:image" content="' . $e($image) . '">';
      }
      $tags[] = '<meta name="twitter:card" content="' . ($image !== '' ? 'summary_large_image' : 'summary') . '">';
      $tags[] = '<meta name="twitter:title" content="' . $e($head->title) . '">';
      if ($head->description !== '') {
        $tags[] = '<meta name="twitter:description" content="' . $e($head->description) . '">';
      }
      if ($image !== '') {
        $tags[] = '<meta name="twitter:image" content="' . $e($image) . '">';
      }
    }
    if ($head->jsonLd) {
      $json = json_encode($this->absoluteLinks($head->jsonLd), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      if ($json !== FALSE) {
        // For the app (Tetra's PageDataService): the path this was written for,
        // and the route's converter back to the app's model, if it has one.
        $attrs = ' data-tetra-path="' . $e($head->path) . '"';
        $fromSchema = $head->status === 200 ? strval($head->match?->route->fromSchema ?? '') : '';
        if ($fromSchema !== '') {
          $attrs .= ' data-tetra-from-schema="' . $e($fromSchema) . '"';
        }
        $tags[] = '<script type="application/ld+json"' . $attrs . '>' . $json . '</script>';
      }
    }
    // Just before </head>, after the shell's own tags.
    $insert = "\n  " . implode("\n  ", $tags) . "\n";
    $pos = stripos($html, '</head>');
    $html = $pos === FALSE ? $html : substr($html, 0, $pos) . $insert . substr($html, $pos);
    // The page's minimal content inside the app's root element (the <body
    // app-root> of Angular's shell), which Angular replaces when it starts.
    if ($head->content !== '' && preg_match('#<body[^>]*>#i', $html, $m, PREG_OFFSET_CAPTURE)) {
      $at = $m[0][1] + strlen($m[0][0]);
      $html = substr($html, 0, $at) . "\n" . $head->content . "\n" . substr($html, $at);
    }
    return $html;
  }

  public static function escape(string $value): string
  {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
  }

  /**
   * Send a response, with an ETag (a hash of exactly what's sent) and, when
   * given, Last-Modified. A client that already has this exact response
   * (If-None-Match) gets 304 Not Modified and no body: a crawler or browser
   * coming back to an unchanged page downloads nothing.
   *
   * Only the ETag decides a 304, never the date alone: a page also changes
   * with things that have no date (the site's settings, tetra.json, the
   * route map), and hashing the whole response catches all of them.
   */
  protected function send(int $status, string $body, string $type = 'text/html; charset=utf-8', string $cache = 'no-cache', ?int $lastModified = NULL): never
  {
    $etag = 'W/"' . sha1($body) . '"';
    header('Content-Type: ' . $type);
    // Pages are checked every time (cheap with the ETag), so a new deploy is picked up at once.
    header('Cache-Control: ' . $cache);
    if ($status === 200) {
      header('ETag: ' . $etag);
      if ($lastModified) {
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
      }
      if (self::etagMatches($_SERVER['HTTP_IF_NONE_MATCH'] ?? '', $etag)) {
        http_response_code(304);
        exit;
      }
    }
    http_response_code($status);
    echo $body;
    exit;
  }

  /**
   * Whether an If-None-Match header names this ETag. Compared weakly
   * (ignoring W/), and ignoring the -gzip or -br Apache's compression adds
   * to an ETag it compresses, which the client then sends back.
   */
  public static function etagMatches(string $ifNoneMatch, string $etag): bool
  {
    if (trim($ifNoneMatch) === '') {
      return FALSE;
    }
    if (trim($ifNoneMatch) === '*') {
      return TRUE;
    }
    $bare = fn(string $tag) => preg_replace('/-(gzip|br|deflate)$/', '', trim(preg_replace('#^W/#', '', trim($tag)), '"'));
    $want = $bare($etag);
    foreach (explode(',', $ifNoneMatch) as $tag) {
      if ($bare(trim(trim($tag), '"')) === $want || $bare(trim($tag)) === $want) {
        return TRUE;
      }
    }
    return FALSE;
  }
}
