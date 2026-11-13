<?php
/**
 * Page routes
 *
 * The app's route map (tetra.json's seo.routes, src/page-routes.json by
 * default; see tetra/page-routes.schema.json): which URLs are public pages
 * and where their metadata comes from. The first route matching a path wins.
 *
 *   PageRoutes::match('/recipes/mac-n-cheese')
 *   // {route: {path: '/recipes/:alias', public: true, ...}, params: {alias: 'mac-n-cheese'}}
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

class PageRoutes
{
  /** The routes, read once per request. */
  private static ?array $routes = NULL;

  /** The route map's file, from the app's root. */
  public static function path(): string
  {
    return SERVER_ROOT . '/' . ltrim(strval(TetraConfig::get('seo.routes')), '/');
  }

  /** The routes, in order; none when the app has no route map (every page private). */
  public static function routes(): array
  {
    if (self::$routes === NULL) {
      $data = is_file(self::path()) ? json_decode(file_get_contents(self::path())) : NULL;
      self::$routes = array_values(array_filter(
        is_object($data) && is_array($data->routes ?? NULL) ? $data->routes : [],
        fn($route) => is_object($route) && is_string($route->path ?? NULL) && str_starts_with($route->path, '/')
      ));
    }
    return self::$routes;
  }

  /**
   * The first route matching a path, with the values of its :name segments.
   * A trailing slash doesn't matter; case does (URLs are case-sensitive).
   *
   * @return object|NULL {route, params}
   */
  public static function match(string $path): ?object
  {
    $segments = self::segments($path);
    foreach (self::routes() as $route) {
      $params = self::matchRoute(self::segments($route->path), $segments);
      if ($params !== NULL) {
        return (object) ['route' => $route, 'params' => (object) $params];
      }
    }
    return NULL;
  }

  /** Whether a matched route may be shown to crawlers. */
  public static function isPublic(?object $match): bool
  {
    return $match !== NULL && ($match->route->public ?? FALSE) === TRUE;
  }

  /** A path's segments, decoded: "/recipes/mac-n-cheese/" is ["recipes", "mac-n-cheese"]. */
  private static function segments(string $path): array
  {
    return array_values(array_map('rawurldecode', array_filter(explode('/', $path), fn($s) => $s !== '')));
  }

  /** The params if the route's segments match the path's, else NULL. */
  private static function matchRoute(array $pattern, array $segments): ?array
  {
    $params = [];
    foreach ($pattern as $i => $part) {
      if ($part === '*' && $i === count($pattern) - 1) {
        return $params;
      }
      if (!array_key_exists($i, $segments)) {
        return NULL;
      }
      if (str_starts_with($part, ':')) {
        $params[substr($part, 1)] = $segments[$i];
      } elseif ($part !== $segments[$i]) {
        return NULL;
      }
    }
    return count($segments) === count($pattern) ? $params : NULL;
  }

  /** Forget what was read, eg. after a test changes the file. */
  public static function reset(): void
  {
    self::$routes = NULL;
  }
}
