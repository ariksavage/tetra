<?php

namespace Core\Users\API;


use \Core\Users\Models\User;
use \Core\Users\Models\UserCategory;
use \Core\Users\Models\UserGroup;
use \Core\Users\Models\UserRole;
use \Core\Database\MySQL\Query\Select as selectQuery;

class BasicUsers extends \Core\Base\API\Base {

  public function __construct()
  {
    parent::__construct('users', '\Core\Users\Models\User', 'Users', 'User');
  }

  public function requiresAuth(): bool
  {
    return FALSE;
  }

  /**
   * Get a list of columns to be used for search queries.
   * To be overridden by specific APIs.
   *
   * @return Array<String> List of column names.
   */
  protected function searchColumns(): Array
  {
    return ['username', 'first_name', 'last_name', 'middle_name', 'email'];
  }

  /**
   * Build a query to list users.
   *
   * @param int $page     Page number of results to return.
   * @param int $per      Number of results per page.
   *
   * @return selectQuery  Query object
   */
  protected function listQuery(int $page = 1, int $per = 20): SelectQuery
  {
    $query = parent::listQuery($page, $per);
    return $query;
  }

  /**
   * Get the current user
   * @return never JSON user data
   */
  public function currentGET()
  {
    // $this->requirePermission('users', 'VIEW OWN');
    if ($currentUser = $this->getCurrentUser()) {
      $currentUser->getGroups();
      $currentUser->getRoles();
      $this->success('user', $currentUser);
    } else {
      var_dump($currentUser);
    }
  }

  /**
   * Log out the current user.
   * @return never JSON response message
   */
  public function logoutGET(): never
  {
    $currentUser = $this->getCurrentUser();
    if ($currentUser) {
      $currentUser->logout();
    }
    $this->successMsg("Successfully logged out.");
  }

  /**
   * Deactivate a user
   * @param  int    $userId User ID
   * @return never          Success message.
   */
  public function deactivatePUT(int $userId): never
  {
    $user = $this->getById($userId);
    if ($user->deactivate()) {
      $this->successMsg('User: ' . $user->name() . ' has been deactivated');
    }
  }

  /**
   * Activate a user
   * @param  int    $userId User ID
   * @return never          Success message.
   */
  public function activatePUT(int $userId): never
  {
    $user = $this->getById($userId);
    if ($user->activate()) {
      $this->successMsg('User: ' . $user->name() . ' has been activated');
    }
  }

  /**
   * Assign roles to the given user.
   *
   * Any roles not included in the request will be removed.
   * @param  [type] $userId User ID
   * @return never
   */
  public function rolesPUT($userId): never
  {
    $this->requirePermission('user_roles', 'UPDATE');
    $user = $this->getById($userId);
    $roles = $this->postValue('roles');

    $success = $user->updateRoles($roles);
    if ($success) {
      $this->successMsg("User ({$userId}) roles successfully updated");
    }
  }

  /**
   * Get a password reset link for the given user.
   * @param  int    $userId User ID
   * @return never          JSON response includes link and text.
   */
  public function passwordResetLinkGET(int $userId)
  {
    $user = $this->getById($userId);
    $host = $this->getValue('host');

    $link = $user->passwordResetLink($host);
    $this->data->link = $link;
    $expiresDays = $this->configValue('password_reset_expires_days');
    $this->data->text  = "Copy the link below to send to the user.";
    $this->data->text .= " This link will expire in <strong>{$expiresDays}</strong> days.";
    $this->success();
  }

  /**
   * Email a user with a password reset link.
   * @param  int    $userId User ID
   * @return never          Response message
   */
  public function passwordResetGET(): never
  {
    $username = $this->getValue('username');
    $user = $this->select()->from('users')
    ->where('username', '=', $username)
    ->execute(TRUE, '\Core\Users\Models\User');
    if ($user) {
      $appName = $this->configValue('name');
      $supportEmail = $this->configValue('support_email');
      $host = $this->getValue('host');
      if (!$host) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $protocol . "://" . $_SERVER['HTTP_HOST'];
      }
      $resetLink = $user->passwordResetLink($host);
      $subject = "Password reset for " . $appName;
      $supportLink = '<a href="mailto:' . $supportEmail . '?subject=password%20reset%20request">contact support</a>';
      $supportLink .= ' if you have questions.';
      $lines = array();
      $lines[] = '<h2>Password Reset Request</h2>';
      $lines[] = '<p>Hello ' . $user->name() . ',</p>';
      $lines[] = '<p>You recently requested to reset your password for your <strong>'.$appName.'</strong> account.</p>';
      $lines[] = '<p><a href="' . $resetLink . '">Reset your password</a></p>';
      $lines[] = '<p>If you did not request a password reset, please ignore this email or '.$supportLink.'</p>';
      $message = implode("\r\n", $lines);
      if (!$user->message($subject, $message)) {
        $this->error("Password reset could not be sent");
      }
    }
    // Sends success message regardless of whether user exists, to prevent brute force guessing usernames
    $this->success('message', 'A password reset link has been sent to your email address.');
  }

  /**
   * Updates the current user's password
   * @return never        Response message
   */
  public function passwordResetPOST(): never
  {
    $currentUser = $this->getCurrentUser();
    $password = $this->postValue('newPassword', '', 'string');
    $password2 = $this->postValue('repeatNewPassword', '', 'string');
    if ($password && $password2 && $password == $password2) {
      if ($currentUser->resetPassword($password, $password2)) {
        $this->success('message', "Password updated for {$currentUser->name()}");
      }
    }
  }

  /**
   * Get a user category by ID
   * @param  int    $catId Category ID
   * @return UserCategory           The category
   */
  protected function getCategoryById(int $catId, array $flags = []): UserCategory
  {
    $query = $this->select()->from('user_categories')->where('id', '=', $catId);
    $category = $query->execute(TRUE, '\Core\Users\Models\UserCategory', $flags);
    return $category;
    return $category;
  }

  /**
   * Set a user's category. Category is removed when $catId is 0
   * @param  int         $userId User ID
   * @param  int|integer $catId  Category ID
   * @return never               JSON response message
   */
  public function categoryPUT(int $userId, int $catId = 0): never
  {
    if ($catId) {
      $category = $this->getCategoryById($catId);
    }
      $user = $this->getById($userId);
    if ($user->setCategory($catId)) {
      if (isset($category)) {
        $this->successMsg("Updated {$user->name()}'s category to {$category->label}");
      } else {
        $this->successMsg("Removed {$user->name()}'s category");
      }
    }
  }

  /**
   * Get all available user categories.
   *
   * @return never               JSON array of categories
   */
  public function categoriesGET(): never
  {
    $query = $this->select()->from('user_categories')->orderBy('label');
    $categories = $query->execute(FALSE, '\Core\Users\Models\UserCategory', ['USER_COUNT']);
    $this->success('categories', $categories);
  }

  /**
   * Get a user category by its ID
   * @param  int    $catId Category ID.
   * @return never         JSON category data.
   */
  public function categoryGET(int $catId): never
  {
    $category = $this->getCategoryById($catId, ['USER_COUNT']);
    $this->success('category', $category);
  }

  /**
   * Save or Update a user category.
   * @return never             JSON updated
   */
  public function categoryPOST()
  {
    $data = $this->postValue('category');
    $category = new UserCategory($data);
    if ($category->save()) {
      $this->success('category', $category, "Successfully saved {$category->label}");
    } else {
      $this->error("Could not save {$category->label}. {$category->getError()}", 500, 'Users', $data);
    }
  }

  /**
   * Delete a user category.
   * @param  int    $categoryId Category's ID
   * @return never              JSON response message
   */
  public function categoryDELETE(int $catId): never
  {
    $category = $this->getCategoryById($catId);
    $name = $category->label;
    if ($category->delete()) {
      $this->successMsg("Deleted category: {$name}");
    } else {
      $this->error($category->getError(), 500, 'Users', $category);
    }
  }

  /**
   * Get a group by its ID
   * @param  int    $groupId Group ID
   * @param  array  $flags   Optional flags to add data to the Group object
   * @return UserGroup       Group object
   */
  protected function getGroupById(int $groupId, array $flags = []): UserGroup
  {
    $query = $this->select()->from('user_groups')->where('id', '=', $groupId);
    $group = $query->execute(TRUE, '\Core\Users\Models\UserGroup', $flags);
    return $group;
  }

  /**
   * Get all user groups.
   *
   * @return void
   */
  public function groupsGET()
  {
    $groups = $this->select()
      ->from('user_groups')
      ->orderBy('label')
      ->execute(FALSE, '\Core\Users\Models\UserGroup', ['USER_COUNT']);
    $this->success('groups', $groups);
  }

  /**
   * Get a user group by ID
   * @param  int    $groupId Group ID
   * @return never          JSON API response: Group data.
   */
  public function groupGET(int $groupId): never
  {
    $group = $this->getGroupById($groupId, ['USER_COUNT']);
    $this->success('group', $group);
  }

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
      $this->successMsg("Added {$user->name()} to {$group->label}");
    } else {
      $this->error($user->getError(['group' => $group->label]), 500, 'Users');
    }
  }

  /**
   * Remove a user from a group
   * @param  int    $userId  User ID
   * @param  int    $groupId Group ID
   * @return never           JSON API response: success message.
   */
  public function groupRemovePUT(int $userId, int $groupId): never
  {
    $group = $this->getGroupById($groupId);
    $user = $this->getById($userId);
    if ($user->removeGroup($groupId)) {
      $this->successMsg("Removed {$user->name()} from {$group->label}");
    } else {
      $this->error($user->getError(['group' => $group->label]), 500, 'Users');
    }
  }

  /**
   * Create or update a user group
   * @return never          JSON API response: Group data.
   */
  public function groupPOST(): never
  {
    $data = $this->postValue('group');
    $group = new UserGroup($data);
    if ($group->save()) {
      $this->success('group', $group, "Successfully saved {$group->label}");
    } else {
      $this->error("Could not save {$group->label}. {$group->getError()}", 500, 'Users', $data);
    }
  }

  /**
   * Delete a user group
   * @param  int    $groupId Group ID
   * @return never           JSON response message.
   */
  public function groupDELETE(int $groupId): never
  {
    $group = $this->getGroupById($groupId);
    $name = $group->label;
    if ($group->delete()) {
      $this->successMsg("Deleted group: {$name}");
    } else {
      $this->error($group->getError(), 500, 'Users', $group);
    }
  }


  /**
   * Saves a user to the database.
   *
   * Overrides base to allow a user to update their own profile
   *
   * @return JSON response
   */
  public function savePOST(): never
  {
    $key = strtolower($this->singular);
    $data = $this->postValue($key);
    $model = new $this->model($data);
    if ($model->isCurrentUser()) {
      $this->reqPermission('UPDATE OWN');
    } else {
      $this->reqPermission('UPDATE');
    }
    $success = $model->save();
    if ($success) {
      $this->success($this->singular, $model, "Successfully saved {$this->singular}.");
    } else {
      $this->error("Could not save {$this->singular}.", $this->plural, $code = 500, $data);
    }
  }
}
?>
