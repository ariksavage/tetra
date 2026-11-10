<?php
/**
 * User model
 *
 * Includes user login, password reset, and permissions logic
 *
 * PHP version 8.4
 *
 *
 * @category   Model
 * @package    Core
 * @author     Arik Savage <ariksavage@gmail.com>
 * @version    1.0
 * @since      2025-01-05
 */

namespace Core\Config\Models;

use \Core\Users\Models\UserGroup;
class Config extends \Core\Base\Models\Base {

  /**
   * Label
   * @var String
   */
  public string $label = '';

  /**
   * Description
   * @var String
   */
  public string $description = '';

  /**
   * Type
   * @var String
   */
  public string $type = '';

  /**
   * Key
   * @var String
   */
  public string $key = '';

  /**
   * Value
   * @var Mixed
   */
  public mixed $value = null;

  /**
   * Value Type
   * @var String
   */
  public string $value_type = '';

  /**
   * Construct the config
   *
   * @param string      $label Singular name for this item, eg "User"
   * @param string      $table Database table where this item is stored, eg "users"
   * @param object|null $data  Data to be mapped onto this item
   * @param array       $flags Additional parameters.
   *
   * @return            $this
   */
  public function __construct(object|null $data = NULL, array $flags = [])
  {
    parent::__construct('Config', 'config', $data, $flags);
    return $this;
  }

  /**
   * Map data onto the model, casting value according to value_type.
   *
   * Runs on construct and on byId(), so loaded values are always typed.
   *
   * @param object $data Data to map.
   *
   * @return $this
   */
  protected function mapData(object $data)
  {
    parent::mapData($data);
    if (property_exists($data, 'value')) {
      $this->value = $this->castValue($data->value);
    }
    return $this;
  }

  /**
   * Cast a raw (database or POST) value to this item's value_type.
   *
   * @param mixed $value Raw value
   *
   * @return mixed Typed value
   */
  public function castValue(mixed $value): mixed
  {
    switch ($this->value_type) {
      case 'number':
        return floatval($value);
      case 'boolean':
        return !!$value;
      case 'object':
        return is_string($value) ? json_decode($value) : $value;
      case 'text':
      case 'longtext':
        return strval($value);
      default:
        return $value;
    }
  }

  /**
   * Check that a value is acceptable for this item's value_type.
   *
   * Unknown value types are allowed so that new types
   * don't block editing before validation is added for them.
   *
   * @param mixed $value Value to test
   *
   * @return bool TRUE if valid
   */
  public function validateValue(mixed $value): bool
  {
    switch ($this->value_type) {
      case 'number':
        return is_numeric($value);
      case 'boolean':
        return is_bool($value) || in_array($value, [0, 1, '0', '1'], TRUE);
      case 'object':
        if (is_string($value)) {
          json_decode($value);
          return json_last_error() === JSON_ERROR_NONE;
        }
        return is_array($value) || is_object($value);
      case 'text':
      case 'longtext':
        return is_null($value) || is_string($value) || is_numeric($value);
      default:
        return TRUE;
    }
  }

  /**
   * Convert value to its string form for storage.
   *
   * @return Array
   */
  public function prepareSave()
  {
    $data = parent::prepareSave();
    switch ($this->value_type) {
      case 'boolean':
        $data['value'] = $this->value ? '1' : '0';
        break;
      case 'object':
        $data['value'] = is_string($this->value) ? $this->value : json_encode($this->value);
        break;
      default:
        $data['value'] = is_null($this->value) ? NULL : strval($this->value);
        break;
    }
    return $data;
  }
}
