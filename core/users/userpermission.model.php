<?php
/**
 * User Rolemodel
 *
 * Represents a role with permissions.
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

namespace Core\Users\Models;

class UserPermission extends \Core\Base\Models\Base {

  /**
   * Human-friendly title
   * @var String
   */
  public string $title = '';

  /**
   * Entity to which this permission applies
   * @var String
   */

  public string $dimension = '';
  /**
   * Action a user is allowed to take:
   * - VIEW,
   * - VIEW OWN
   * - CREATE
   * - UDATE
   * - UPDATE OWN
   * - DELETE
   * - DELETE OWN
   * - * (ALL)
   *
   * @var String
   */
  public string $action = '';

  /**
   * Human-friendly description of the permission
   */
  public string $description = '';

  /**
   * Construct the model
   *
   * @param object|null $data  Data to be mapped onto this item
   * @param array       $flags Additional parameters.
   *
   * @return            $this
   */
  public function __construct(object|null $data = NULL, array $flags = [])
  {
    parent::__construct('Permission', 'user_permissions', $data, $flags = []);
    return $this;
  }
}
