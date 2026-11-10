<?php
namespace Core;

class ClassLoader
{
  protected string $baseDir;
  protected string $namespacePrefix;

  public function __construct()
  {
    $this->register();
  }

  public function register(): void
  {
    spl_autoload_register([$this, 'loadClass']);
  }

  /**
   * Convert a full classname into a consistent folder structure.
   *
   * eg Core\Users\Models\User -> users/user.model.php
   *
   * starting Core is ignored.
   * Last segment (separated by \) [User] is the name.
   * Second to last segment [Models] is the type, used as a singular in the extension
   * Anything between Core ... {type} [Users] is treated as a path.
   *
   * So Core -> [nothing]
   * Core\Users -> /Users
   * Core\Users\Models -> /Users/*.model.php
   * Core\Users\Models\User -> /Users/User.model.php
   *
   * @param  string $class Namespaced classname
   * @return string        Mapped path
   */
  private function toPath(string $class): string
  {
    $parts = explode('\\', $class);
    $core = array_shift($parts);
    $name = array_pop($parts);
    $type = array_pop($parts);
    $ext  =$type ?  preg_replace('/s$/', '', trim(strtolower($type))) : '';
    $ext .= '.php';
    $path = implode('/', $parts);
    if (!$path) {
      $path = "$name/{$name}.{$ext}";
    } else {
      $path .= "/{$name}.{$ext}";
    }
    return $path;
  }

  /**
   * Requires the relevant file for the class being called.
   * This will look for the path (@see toPath()):
   *   - First in the app's core: higher level folder.
   *   - Then in the Tetra core.
   *
   * This allows the application to extend and overide core functionality as needed
   *
   * @param  string $class Fully namespaced class name
   * @return void          The relevant file is loaded via require.
   */
  public function loadClass(string $class): void
  {
    // Only handle Core namespace
    if (strpos($class, 'Core\\') !== 0) {
      return;
    }

    // Skip if already defined (e.g., built-in like \Imagick)
    if (class_exists($class, false)) {
      return;
    }

    $path = $this->toPath($class);

    // The app's own core first, so it can add types and override Tetra's
    // classes, then Tetra's. In a deploy both are the same folder.
    foreach ([APP_CORE_ROOT, CORE_ROOT] as $root) {
      $file = $root . '/' . $path;
      if (file_exists($file)) {
        require($file);
        return;
      }
    }
    throw new \Exception($class . ' at ' . $path . ' not found');
  }
}
