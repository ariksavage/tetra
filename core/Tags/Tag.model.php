<?php
/**
 * Tag model
 *
 *
 *
 * PHP version 8.4
 *
 *
 * @category   Model
 * @package    Core
 * @author     Arik Savage <ariksavage@gmail.com>
 * @version    1.0
 * @since      2025-04-02
 */

namespace Core\Tags\Models;

class Tag extends \Core\Base\Models\Base {
  /**
   * Tag name / label
   * @var string
   */
  public string $name = '';

  /**
   * Tag name / label
   * @var string
   */
  public string $category = '';
  /**
   * Construct the tag
   *
   * @param string      $label Singular name
   * @param string      $table Database table where this item is stored
   * @param object|NULL $data  Data to be mapped onto this item
   * @param array       $flags Additional parameters.
   *
   * @return            $this
   */
  public function __construct(object|NULL $data = NULL, array $flags = [])
  {
    parent::__construct('tag', 'tags', $data, $flags);
    unset($this->tags);
    return $this;
  }
}
