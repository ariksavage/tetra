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
 * @author     Eric Savage <eric@hiapti.com>
 * @version    1.0
 * @since      2025-04-02
 */

namespace Core\Tags\Models;

class Tag extends \Core\Base\Models\Base {
  /**
   * Tag name / label
   * @var string
   */
  public string $label = '';

  /**
   * Color code. Unused?
   * @var string
   */
  public string $hex = '';

  /**
   * Tenant ID. Unused?
   * @var integer
   */
  public int $tenant_id = 1;
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
