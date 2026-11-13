<?php
/**
 * Discovery: robots.txt and sitemap.xml
 *
 * Both are generated on each request (the page renderer answers
 * /robots.txt and /sitemap.xml), never files in the web root, so no deploy
 * can overwrite an app's or a site's rules.
 *
 * robots.txt, in layers, each headed by a comment saying where it came from:
 *   1. Tetra: allow every crawler (one User-agent: * group, naming none, so an
 *      app's or site's group for one crawler never clashes with Tetra's);
 *   2. the route map: Disallow lines for its private pages;
 *   3. the app's tetra.json (seo.robots: mode, rules);
 *   4. this site's own (the Configuration page: seo, Robots rules and mode).
 * A layer in "replace" mode drops the layers before it. Then the sitemap.
 * When tetra.json's seo.indexable is false: disallow everything, nothing else.
 *
 * sitemap.xml: the route map's public pages without parameters, and the
 * records of public model routes whose mapper lists them (sitemapEntries()).
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

class Discovery
{
  use \Core\Base\Traits\Common;
  use \Core\Database\MySQL\Traits\Queries;

  /** After sitemapXml(): when its newest page changed (unix time), or NULL. */
  public ?int $lastModified = NULL;

  /** @param \Closure $absolute fn(string $path): string, a full URL on the site's address. */
  public function __construct(private \Closure $absolute) {}

  public function robotsTxt(): string
  {
    if (!TetraConfig::get('seo.indexable')) {
      return "# This site isn't indexed (tetra.json: seo.indexable is false).\nUser-agent: *\nDisallow: /\n";
    }

    $layers = [];
    $layers[] = ['# Tetra: every crawler may crawl the site', 'User-agent: *', 'Allow: /'];

    $private = array_values(array_unique(array_filter(array_map(
      fn($route) => ($route->public ?? FALSE) === TRUE ? NULL : self::robotsPath($route->path),
      PageRoutes::routes()
    ))));
    if ($private) {
      $layers[] = array_merge(['# The route map: private pages (in the User-agent: * group above)'], array_map(fn($p) => "Disallow: {$p}", $private));
    }

    $app = array_values(array_filter(array_map('strval', (array) TetraConfig::get('seo.robots.rules')), fn($line) => trim($line) !== ''));
    if (TetraConfig::get('seo.robots.mode') === 'replace') {
      $layers = [];
    }
    if ($app) {
      $layers[] = array_merge(['# The app (tetra.json, seo.robots)'], $app);
    }

    $siteRules = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', strval($this->siteValue('robots_rules'))))));
    if ($this->siteValue('robots_mode') === 'replace') {
      $layers = [];
    }
    if ($siteRules) {
      $layers[] = array_merge(['# This site (Configuration page: Robots rules)'], $siteRules);
    }

    $layers[] = ['Sitemap: ' . ($this->absolute)('/sitemap.xml')];
    return implode("\n\n", array_map(fn($layer) => implode("\n", $layer), $layers)) . "\n";
  }

  /**
   * The sitemap, or NULL when the site isn't indexed.
   */
  public function sitemapXml(): ?string
  {
    if (!TetraConfig::get('seo.indexable')) {
      return NULL;
    }
    $entries = [];
    foreach (PageRoutes::routes() as $route) {
      if (($route->public ?? FALSE) !== TRUE) {
        continue;
      }
      if (!str_contains($route->path, ':') && !str_contains($route->path, '*')) {
        $entries[$route->path] = ['path' => $route->path, 'lastmod' => NULL];
      }
      if (!empty($route->model) && !empty($route->schema)) {
        $class = '\\Core\\' . ltrim($route->schema, '\\');
        try {
          if (class_exists($class) && method_exists($class, 'sitemapEntries')) {
            foreach ((new $class())->sitemapEntries() as $entry) {
              if (!empty($entry['path'])) {
                $entries[$entry['path']] = $entry;
              }
            }
          }
        } catch (\Throwable $e) {
          error_log("Sitemap: {$route->schema} failed to list its pages: " . $e->getMessage());
        }
      }
    }
    $dates = array_filter(array_map(fn($entry) => intval($entry['lastmod'] ?? 0), $entries));
    $this->lastModified = $dates ? max($dates) : NULL;
    $e = fn($v) => htmlspecialchars(strval($v), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
    foreach ($entries as $entry) {
      $xml[] = '  <url><loc>' . $e(($this->absolute)($entry['path'])) . '</loc>'
        . (!empty($entry['lastmod']) ? '<lastmod>' . $e(gmdate('Y-m-d', intval($entry['lastmod']))) . '</lastmod>' : '')
        . '</url>';
    }
    $xml[] = '</urlset>';
    return implode("\n", $xml) . "\n";
  }

  /**
   * A route's path as a robots.txt path: ":name" segments match anything
   * ("*"), a final "/*" is everything below ("/admin/"), and an exact path
   * ends with "$" so it doesn't block others it's the start of
   * ("/recipes/new$" leaves "/recipes/new-york-cheesecake" alone).
   */
  public static function robotsPath(string $path): ?string
  {
    if ($path === '/' || $path === '') {
      return NULL;
    }
    if (str_ends_with($path, '/*')) {
      return preg_replace('#/:[^/]+#', '/*', substr($path, 0, -1));
    }
    return preg_replace('#/:[^/]+#', '/*', rtrim($path, '/')) . '$';
  }

  /** A site's own robots setting (type seo), or '' (no row, or no database). */
  private function siteValue(string $key): string
  {
    try {
      return strval($this->configValue($key, 'seo') ?? '');
    } catch (\Throwable $e) {
      return '';
    }
  }
}
