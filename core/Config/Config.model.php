<?php
/**
 * Config model
 *
 * Configuration options / values
 *
 * PHP version 8.4
 *
 * @category   Model
 * @package    Core
 * @author     Arik Savage <ariksavage@gmail.com>
 * @version    1.0
 * @since      2025-01-10
 */

namespace Core\Config\Models;

class Config extends \Core\Base\Models\Base {

  /**
   * User facing label
   * @var String
   */
  public string $label = '';

  /**
   * User facing description
   * @var String
   */
  public string $description = '';

  /**
   * General type of the configuration, essentially a category
   * @var String
   */
  public string $type = '';

  /**
   * Config key
   * @var String
   */
  public string $key = '';

  /**
   * Config value
   * @var String
   */
  public mixed $value = '';

  /**
   * Type of the value: string, number, boolean, etc
   * @var String
   */
  public string $value_type = '';

  /**
   * Construct the model
   *
   * @param string      $label Singular name for this item, eg "User"
   * @param string      $table Database table where this item is stored, eg "users"
   * @param object|null $data  Data to be mapped onto this item
   * @param array       $flags Additional parameters.
   *
  rint->id);
    // $blueprint->created_by = intval($blueprint->created_by);
    // $blueprint->modified_by = intval($blueprint->modified_by);
    // $blueprint->finalized = $blueprint->finalized == '1'; * @return            $this
   */
  public function __construct(object|null $data = NULL, array $flags = [])
  {
    parent::__construct('Config', 'config', $data, $flags);
    return $this;
  }

  /**
   * Take data from the database, or POST
   * and map it to the model.
   *
   * @param object $data Data to map.
   *
   * @return $this.
   */
  protected function mapData(object $data)
  {
    parent::mapData($data);
    switch ($this->value_type) {
      case 'text':
      case 'longtext':
        break;
      case 'number':
        $this->value = floatval($this->value);
        break;
      case 'boolean':
        $this->value = !!$this->value;
        break;
      case 'object':
        $this->value = json_decode($this->value);
      default:
        break;
    }
    return $this;
  }

  public function setValue($value)
  {
    $this->value = $value;
    $this->save();
  }
}
