<?php
/**
 * Knowledge Direct User API
 *
 * Manage all API responses pertaining primarily to users.
 *
 * PHP version 8.4
 *
 *
 * @category   API
 * @package    Core
 * @author     Arik Savage <ariksavage@gmail.com>
 * @version    1.0
 * @since      2025-01-05
 */

namespace Core\Users\API;

use \Core\Users\Models\User;
use \Core\Users\Models\UserCategory;
use \Core\Users\Models\UserGroup;
use \Core\Users\Model\UserRole;
use \Core\Database\MySQL\Query\Select as selectQuery;

/**
 * Users API controller class for user operations
 */
class Users extends BasicUsers {


  /**
   * Add a user to a user group.
   * @param  int    $userId  User ID
   * @param  int    $groupId Group ID
   * @return never           JSON API response: success message.
   */
  public function groupAddPUT(int $userId, int $groupId): never
  {
    $user = $this->getById($userId);
    $group = $this->getGroupById($groupId);
    if ($user->addGroup($groupId)) {
      $nCourses = $this->enrollCoursesByGroup($userId, $groupId);
      $nlearningPaths = $this->enrollLearningPathsByGroup($userId, $groupId);


      $message = "Added {$user->name()} to {$group->label}. ";
      if ($nlearningPaths) {
        $message .= "Enrolled in {$nlearningPaths} learning paths. ";
      }
      if ($nCourses) {
        $message .= "Enrolled in {$nCourses} courses. ";
      }
      $this->successMsg($message);
    } else {
      $this->error($user->getError(['group' => $group->label]), 500, 'Users');
    }
  }

  /**
   * Get all user roles.
   *
   * @return void
   */
  public function rolesGET()
  {
    $roles = $this->select()->from('roles')->orderBy('label')->execute(FALSE, '\Core\Users\Models\UserRole');
    $this->success('roles', $roles);
  }
}
