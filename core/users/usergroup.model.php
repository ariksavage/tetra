<?php
/**
 * User Rolemodel
 *
 * Represents a Group of users.
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

class UserGroup extends \Core\Base\Models\Base {
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
   * Group is publicly visible
   * @var boolean
   */
  public bool $public = FALSE;

  /**
   * Tenant ID
   */
  public int $tenant_id = 1;

    /**
   * Count of users in this group
   * @var int
   */
  public int $count = 0;

  public array $users = [];

  public function __construct(object|NULL $data = NULL, array $flags = [])
  {
    parent::__construct('group', 'user_groups', $data, $flags);
    return $this;
  }

  /**
   * Get the total number of users in the group.
   * @return  int  Number of users
   */
  public function getUserCount(): int
  {
    $users = $this->select()
    ->from('user_group_assignments')
    ->where('group_id', '=', $this->id)
    ->execute();
    return $this->count = count($users);
  }

  /**
   * Get all users in the group.
   * @return array<User>  Array of users.
   */
  public function getUsers()
  {
    $users = $this->select()
    ->from('user_group_assignments')
    ->leftJoin('users', 'user_id', 'id')
    ->where('group_id', '=', $this->id)
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
   * Provide validation before this Group is allowed to be deleted.
   *
   * Group may not be deleted if it has members.
   * @return bool TRUE if group may be deleted.
   */
  protected function allowDelete()
  {
    $users = $this->getUserCount();
    if ($users > 0) {
      $error = "Can not delete. Group {$this->label} has {$users} ";
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
