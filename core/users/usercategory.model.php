<?php
/**
 * User Rolemodel
 *
 * Represents a Category of users.
 *
 * PHP version 8.4
 *
 * @category   Model
 * @package    Core
 * @author     Arik Savage <ariksavage@gmail.com>
 * @version    1.0
 * @since      2025-01-05
 */

namespace Core\Users\Models;

class UserCategory extends \Core\Base\Models\Base {
  /**
   * Name of the group
   * @var string
   */
  public string $label = '';

  /**
   * Description of the group
   * @var string
   */
  public string $description = '';

  /**
   * Tenant ID
   * @var int
   */
  public int $tenant_id = 1;

  /**
   * Count of users in this category
   * @var int
   */
  public int $count = 0;

  public function __construct(object|NULL $data = NULL, array $flags = [])
  {
    parent::__construct('category', 'user_categories', $data, $flags);
    return $this;
  }

  /**
   * Get a count of users in tis category.
   * @return int Number of users in the category.
   */
  protected function getUserCount(): int
  {
    $users = $this->select()
    ->from('users')
    ->where('category_id', '=', $this->id)
    ->execute();
    return $this->count = count($users);
  }

  /**
   * Get all users in the category.
   * @return array<User>  Array of users.
   */
  protected function getUsers()
  {
    $users = $this->select()
    ->from('users')
    ->where('category_id', '=', $this->id)
    ->execute(FALSE, '\Core\Users\Models\User');
    return $this->users = $users;
  }

  /**
   * Get additional related data based on provided flags
   * @param  array  $flags Optional flags for additional data.
   * @return $this
   */
  protected function postLoad(array $flags = [])
  {
    parent::postLoad($flags);
    if (in_array('USER_COUNT', $flags)) {
      $this->getUserCount();
    }
    if (in_array('WITH_USERS', $flags)) {
      $this->getUsers();
    }
    return $this;
  }

  /**
   * Provide validation before this Category is allowed to be deleted.
   *
   * Group may not be deleted if it has members.
   * @return bool TRUE if group may be deleted.
   */
  protected function allowDelete()
  {
    $users = $this->getUserCount();
    if ($users > 0) {
      $error = "Can not delete. Category {$this->label} has {$users} ";
      $error .= $users > 1 ? 'users' :'user';
      $this->setError($error);
      return FALSE;
    } else {
      return TRUE;
    }
  }

  /**
   * Convert the model to an array for use in a query.
   *
   * Apply any other necessary business logic before saving
   * - remove count and/or users before saving
   *
   * @return Array Data to be saved.
   */
  public function prepareSave()
  {
    $data = parent::prepareSave();
    unset($data['count']);
    unset($data['users']);
    return $data;
  }
}
