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

namespace Core\Users\Models;

use \Core\Users\Models\UserCategory;
use \Core\Users\Models\UserGroup;
class BasicUser extends \Core\Base\Models\Base {

  /**
   * Username
   * @var String
   */
  public string $username = '';

  /**
   * Name prefix eg. "Ms.", "Dr.", "Mr."
   * @var String
   */
  public string $name_prefix = '';

  /**
   * First Name
   * @var String
   */
  public string $first_name = '';

  /**
   * Middle Name
   * @var String
   */
  public string $middle_name = '';

  /**
   * Last Name
   * @var String
   */
  public string $last_name = '';

  /**
   * Name Suffix eg. "jr.", "OBE"
   * @var String
   */
  public string $name_suffix = '';

  /**
   * Email address
   * @var String
   */
  public string $email = '';

  /**
   * Password
   * @var String
   */
  protected string $password = '';

  /**
   * Roles
   * @var array
   */
  public array $roles = [];

  /**
   * User groups
   * @var array
   */
  public array $user_groups = [];

  /**
   * Category ID
   * @var integer
   */
  public int $category_id = 0;
  /**
   * Category Object
   * @var object
   */
  public object $category;

  /**
   * User status
   * 'Active', 'Deactivated', 'Pending'
   * @var string
   */
  public string $status = 'Pending';


  /**
   * Construct the user
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
    $this->category = new \stdClass();
    parent::__construct('User', 'users', $data, $flags);
    return $this;
  }

  /**
   * Get additional related data based on provided flags
   * @param  array  $flags Flags passed in to get additional data
   * @return $this         Returns the current user.
   */
  protected function postLoad(array $flags = [])
  {
    if (in_array('WITH_TAGS', $flags)) {
      $this->getTags();
    }
    if (in_array('WITH_GROUPS', $flags)) {
      $this->getGroups();
    }
    if (in_array('WITH_CATEGORY', $flags)) {
      $this->getCategory();
    }
    if (in_array('WITH_ROLES', $flags)) {
      $this->getRoles();
    }
    return $this;
  }

  /**
   * Get full category data.
   * @return \Core\Users\Models\UserCategory Category
   */
  public function getCategory(): object
  {
    $query = $this->select()->from('user_categories')
    ->where('id', '=', $this->category_id);
    $category =  $query->execute(TRUE, '\Core\Users\Models\UserCategory');
    if ($category) {
      $this->category = $category;
    }
    return $this->category;
  }

  /**
   * Set the user's category.
   * @param int|integer $catId Category ID
   *
   * @return bool              TRUE on success.
   */
  public function setCategory(int $catId = 0): bool
  {
    $this->category_id = $catId;
    if ($this->save()) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Get all user groups to which this user belongs.
   * @return array User Groups
   */
  public function getGroups(): array
  {
    $this->user_groups = $this->select(['`user_groups`.*'])->from('user_groups')
    ->leftJoin('user_group_assignments', 'id', 'group_id')
    ->where('`user_group_assignments`.`user_id`', '=', $this->id)
    ->execute(FALSE, '\Core\Users\Models\UserGroup');
    return $this->user_groups;
  }

  /**
   * Tests if this user is in a particular group
   * @param  int    $groupId Group ID
   * @return bool            TRUE if the user is a member of the group.
   */
  protected function inGroup(int $groupId): bool
  {
    if (!$this->user_groups) {
      $this->getGroups();
    }
    $in = FALSE;
    foreach ($this->user_groups as $group) {
      if ($group->id == $groupId) {
        $in = TRUE;
        break;
      }
    }
    return $in;
  }

  /**
   * Adds this user as a member of the given group.
   * @param int $groupId  Group ID to which the user will be added.
   *
   * @return bool         TRUE on success
   */
  public function addGroup(int $groupId): bool
  {
    if ($this->inGroup($groupId)) {
      $this->setError("{$this->name()} is already in group \"{{group}}\".");
      return FALSE;
    }
    $data = array(
      'user_id' => $this->id,
      'group_id' => $groupId
    );
    if ($this->insert($data)->into('user_group_assignments')->execute()) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Removes this user as a member from the given group.
   * @param int $groupId  Group ID from which the user will be removed.
   *
   * @return bool         TRUE on success
   */
  public function removeGroup(int $groupId): bool
  {
    if (!$this->inGroup($groupId)) {
      $this->setError("{$this->name()} is not in group \"{{group}}\".");
      return FALSE;
    }
    if ($this->deleteQuery('user_group_assignments')
      ->where('user_id', '=', $this->id)
      ->and('group_id', '=', $groupId)
      ->execute()) {
      return TRUE;
    } else {
      return FALSE;
    }
  }


  /**
   * Convert the model to an array for use in a query.
   *
   * And apply any other necessary business logic
   * before saving
   *
   * - Ensure the user's password is hashed, if provided.
   * - Remove role data (managed separately)
   * - Remove group data (managed separately)
   * - Remove category data (managed separately)
   * @return array
   */
  public function prepareSave()
  {
    $data = parent::prepareSave();
    if ($this->password) {
      if (strlen($this->password) == 60) { // password is hashed
        $data['password'] = $this->password;
      } else {
        $data['password'] = $this->passwordHash($this->password);
      }
    }
    if (!$this->id && !isset($data['password'])) {
      $data['password'] = $this->generateAuthToken();
    }
    if ($data && isset($data['roles'])){
      if (is_string($data['roles']) && json_decode($data['roles'])) {
        $data['roles'] = json_decode($data['roles']);
        if (is_array($data['roles'])){
          $this->updateRoles($data['roles']);
        }
      }
      unset($data['roles']);
    }
    unset($data['category']);
    if (isset($data['user_groups'])){
      if (is_string($data['user_groups']) && json_decode($data['user_groups'])) {
        $data['user_groups'] = json_decode($data['user_groups']);
        $this->updateGroups($data['user_groups']);
      }
      unset($data['user_groups']);
    }
    return $data;
  }

  /**
   * Operations to be conducted after the model is saved.
   *
   * By default: refresh this model to acurately reflect the database.
   *
   * @return $this
   */
  protected function postSave()
  {
    $this->getRoles();

    if (count($this->roles) == 0) {
      $roles = $this->select()->from('user_roles')->where('default', '=', '1')->execute(FALSE);
      foreach ($roles as $role) {
        $this->addRole($role->id);
      }
    }
    $this->getRoles();
    return parent::postSave();
  }

  /**
   * Post-delete cleanup.
   *
   * eg. Delete items from related tables
   *
   * @return $this
   */
  public function postDelete()
  {
    if ($this->deleteQuery('user_role_assignments')->where('user_id', '=', $this->id)->execute()) {
      if ($this->deleteQuery('user_group_assignments')->where('user_id', '=', $this->id)->execute()) {
        return parent::postDelete();
      }
    }
  }


  /**
   * Get the user's full name as a single string:
   *
   * {prefix} {First} {Middle} {Last} {suffix}
   *
   * ignoring any missing segments
   *
   * @return String Full name
   */
  public function name()
  {
    $names = array();
    $names[] = $this->name_prefix;
    $names[] = $this->first_name;
    $names[] = $this->middle_name;
    $names[] = $this->last_name;
    $names = array_filter($names);
    $name = join(' ', $names);
    if ($this->name_suffix) {
      $name .= ', ' . $this->name_suffix;
    }
    return $name;
  }

  /**
   * Deactivate this user, so they can no longer log in until they are re-activated
   * @return bool TRUE if successful
   */
  public function deactivate(): bool
  {
    $this->status = 'deactivated';
    if ($this->save()) {
      $this->cleanupSessions();
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Activate the user.
   * @return bool TRUE if successful
   */
  public function activate(): bool
  {
    $this->status = 'active';
    if ($this->save()) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Validate username and password.
   * if everything matches:
   * Start a new session for the user.
   *
   * @param String $username Username
   * @param String $password Password
   *
   * @return string token
   */
  public function login(string $username, string $password): string
  {
    $this->byUsername($username, TRUE);
    if ($this->testPassword($password)) {
      $expiresDays = $this->configValue('session_expires_days');
      return $this->startSession($expiresDays);
    } else {
      $this->error("Access Denied.", 401, "Login");
    }
  }

  /**
   * End the current user's session.
   *
   * @return bool Success.
   */
  public function logout(): bool
  {
    $_SESSION['authorization_token'] = NULL;
    $_SESSION['user'] = NULL;
    return $this->endSession();
  }

  /**
   * Send an email to this user
   * @param  string $subject Subject line
   * @param  string $body    HTML Body
   * @return bool            TRUE on success
   */
  public function message(string $subject, string $body): bool
  {
    return $this->sendEmail($this->email, $subject, $body);
  }

  /**
   * Start a new session and generate a link to
   * allow a user to reset their password.
   *
   * @var String $host  Base URL of the request.
   *
   * @return String     Reset Link
   */
  public function passwordResetLink(string $host)
  {
    // end any open sessions
    $now = date('Y-m-d h:i:s');
    $this->update(['date_ended' => $now], 'user_sessions')
    ->where('user_id', '=', $this->id)
    ->and('date_ended', 'IS NULL')
    ->execute();
    // start a new reset session
    $expiresDays = $this->configValue('password_reset_expires_days');
    $token = $this->startSession($expiresDays);
    $link = "{$host}/password-reset/{$token}";
    return $link;
  }

  /**
   * Get user by the current authorization token
   *
   * @return User
   */
  public function byToken()
  {
    $token = $this->getAuthToken();
    $now = date('Y-m-d h:i:s');
    if (!$token) {
      return FALSE;
    }
    $sessionQuery = $this->select(['user_id'], 'user_sessions')
      ->where('token', '=', $token)
      ->and('date_expires', '>', $now)
      ->and('date_ended', 'IS NULL');
    if ($session = $sessionQuery->execute(TRUE)) {
      $q = $this->select()
        ->where('id', '=', $session->user_id);
      if ($data = $q->execute(TRUE)) {
        $this->mapData($data);
        return TRUE;
      } else {
        return FALSE;
      }
    } else {
      return FALSE;
    }
  }

  /**
   * Get a user by their username.
   * @param String $username Username
   *
   * @return User.
   */
  public function byUsername(string $username, bool $active = FALSE)
  {
    $q = $this->select()
      ->where('username', '=', $username);
    if ($active) {
      $q->and('status', '=', 'active');
    }
    $data = $q->execute(TRUE);
    if ($data) {
      $this->mapData($data);
      return $this;
    } else {
      $this->error("{$username} not found", 404, "User not found");
    }
  }

  /**
   * Test password hash against the password that is provided.
   *
   * Note that password_hash() returns the algorithm,
   * cost and salt as part of the returned hash.
   *
   * Therefore, all information that's needed to verify the hash is included in it.
   * This allows the verify function to verify the hash without needing separate
   * storage for the salt or algorithm information.
   *
   * @param String $password   Un-encrypted password
   *
   * @return bool           Password verified
   */
  public function testPassword(string $password): bool
  {
    $passwordHash = $this->password;
    if (!$passwordHash) {
      return FALSE;
    }
    return password_verify($password, $passwordHash);
  }

  /**
   * Create a hash of the user's password.
   * In this case, we want to increase the default cost for BCRYPT to 12.
   * Note BCRYPT will always be 60 characters.
   *
   * @param String $password   Un-encrypted password
   *
   * @return String            Encrypted password
   */
  protected function passwordHash(string $password)
  {
    $options = [
        'cost' => 12,
    ];
    return password_hash($password, PASSWORD_BCRYPT, $options);
  }

  /**
   * Update the user's password
   * @param  string $password  Password
   * @param  string $password2 Repeated password
   * @return bool           TRUE on success
   */
  public function resetPassword(string $password, string $password2)
  {
    if ($password !== $password2) {
      return FALSE;
    }
    $hash = $this->passwordHash($password);
    $this->password = $hash;
    if ($this->save()) {
      $this->cleanupSessions();
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Create a random auth token from the username and current timestamp
   * @return string Auth token
   */
  private function generateAuthToken(): string
  {
    $token = md5($this->username . date('Y-m-d-h-i-s'));
    $_SESSION['authorization_token'] = $token;
    return $token;
  }

  /**
   * Start a new session in the user_sessions table
   * with a new auth token.
   *
   * @return String  Authorization token or empty on failure
   */
  protected function startSession($expiresDays): string
  {
    $token = $this->generateAuthToken();
    $data = array();
    $data['user_id'] = $this->id;
    $data['token'] = $token;
    $data['date_started'] = date('Y-m-d h:i:s');
    $data['date_last_access'] = $data['date_started'];
    $data['date_expires'] = date('Y-m-d h:i:s', strtotime("+{$expiresDays} days"));
    if ($this->insert($data, 'user_sessions')->execute()) {
      $_SESSION['authorization_token'] = $token;
      $_SESSION['user'] = $this;
      return $token;
    } else {
      return '';
    }
  }

  /**
   * Keep the current session alive
   * by updating its last_active value
   *
   * @param String $token  Authorization token matching a session for this user.
   *
   * @return bool       Success.
   */
  public function updateSession(string $token = '')
  {
    $token = $this->getAuthToken();
    $data = array();
    $now = date('Y-m-d h:i:s');
    $data['date_last_access'] = $now; // updated by the table itself
    $expiresDays = $this->configValue('session_expires_days');
    $data['date_expires'] = date('Y-m-d h:i:s', strtotime("+{$expiresDays} days"));
    if ($this->update($data, 'user_sessions')->where('token', '=', $token)->and('user_id', '=', $this->id)->execute()) {
      $this->cleanupSessions();
      return TRUE;
    }
  }

  /**
   * End the current session
   * add the date_ended value to user_sessions
   *
   * @return bool  Success
   */
  protected function endSession()
  {
    $token = $this->getAuthToken();
    $data = array();
    $data['date_ended'] = date('Y-m-d h:i:s');
    return $this->update($data, 'user_sessions')
      ->where('token', '=', $token)
      ->and('user_id', '=', $this->id)
      ->and('date_ended', 'IS NULL')
      ->execute();
  }

  /**
   * End any outstanding sessions
   *
   * @return bool  Success
   */
  protected function cleanupSessions(): bool
  {
    $now = date('Y-m-d h:i:s');
    $data = array('date_ended' => $now);
      return $this->update($data, 'user_sessions')
      ->where('user_id', '=', $this->id)
      ->and('date_expires', '<', $now)
      ->and('date_ended', 'IS NULL')
      ->execute();
  }

  /**
   * Get all roles assigned to this user.
   *
   * @return void Roles are assigned to the Roles property.
   */
  public function getRoles()
  {
    $this->roles = array();
    $this->roles = $this->select(['user_roles.*'])
      ->from('user_role_assignments')
      ->leftJoin('user_roles', 'role_id', 'id')
      ->where('user_role_assignments.user_id', '=', $this->id)
      ->execute(FALSE, '\Core\Users\Models\UserRole');
  }

  /**
   * Update user role assignments for this user.
   *
   * Adds any roles that are not already assigned
   *
   * Removes any roles not included in $roles here.
   *
   * @param  array  $roles array of role IDs
   * @return bool          TRUE if successful.
   */
  public function updateRoles(array $roles): bool
  {
    $roles = array_map(function($role) {
      return is_int($role) ? $role : $role->id;
    }, $roles);
    $existingRolesQuery = $this->select()->from('user_role_assignments')->where('user_id', '=', $this->id);
    $existing = $existingRolesQuery->execute();
    $existingRoles = array_map( function ($item) {
      return intval($item->role_id);
    }, $existing);

    // Add un-assigned roles
    $addRoles = array_diff($roles, $existingRoles);
    $success = TRUE;
    if ($success && count($addRoles)) {
      foreach ($addRoles as $roleId) {
        if (!$this->addRole($roleId)) {
          $success = FALSE;
          $this->error("Could not assign role ({$role}) to user ({$this->id})");
          break;
        }
      }
    }

    // Remove roles not included in the function's parameters.
    $removeRoles = array_diff($existingRoles, $roles);
    if ($success && count($removeRoles)) {
      foreach ($removeRoles as $roleId) {
        if (!$this->removeRole($roleId)) {
          $success = FALSE;
          $this->error("Could not assign role ({$roleId}) to user ({$this->id})");
        }
      }
    }
    return $success;
  }

  public function updateGroups(array $newGroups): bool
  {
    $success = TRUE;
    $existingGroups = $this->getGroups();
    $existingGroupIds = array_map(function($group){return $group->id;}, $existingGroups);
    $newGroupIds = array_map(function($group){return $group->id;}, $newGroups);
    $addGroups = array_diff($newGroupIds, $existingGroupIds);
    foreach($addGroups as $id) {
      if (!$this->addGroup($id)) {
        $success = FALSE;
        $this->error("Could not add user ({$this->id}) to group ({$id})");
        break;
      }
    }
    $removeGroups = array_diff($existingGroupIds, $newGroupIds);
    foreach($removeGroups as $id) {
      if (!$this->removeGroup($id)) {
        $success = FALSE;
        $this->error("Could not remove user ({$this->id}) from group ({$id})");
        break;
      }
    }
    return $success;
  }

  /**
   * Assign a role to this user.
   * @param int $roleId Role ID
   *
   * @return bool       Success
   */
  protected function addRole(int $roleId): bool
  {
    $success = TRUE;
    $data = ['user_id' => $this->id, 'role_id' => $roleId];
    if (!$this->insert($data, 'user_role_assignments')->execute()) {
      $success = FALSE;
      $this->error("Could not assign role ({$roleId}) to user ({$this->id})");
    }
    return $success;
  }

  /**
   * Remove a role from this user.
   * @param int $roleId Role ID
   *
   * @return bool       Success
   */
  protected function removeRole(int $roleId): bool
  {
    $success = TRUE;
    $deleteRoleQuery = $this->deleteQuery('user_role_assignments')
      ->where('user_id', '=', $this->id)
      ->and('role_id', '=', $roleId);
    if (!$deleteRoleQuery->execute()) {
      $success = FALSE;
      $this->error("Could not assign role ({$roleId}) to user ({$this->id})");
    }
    return $success;
  }

  /**
   * Check all roles for the given permission.
   * @param  string  $dimension Permission dimension
   * @param  string  $action    Permission action (VIEW, UPDATE, DELETE, etc)
   * @return bool               TRUE if permission is found.
   */
  public function hasPermission(string $dimension, string $action = ''): bool
  {
    $this->getRoles();
    foreach ($this->roles as $role) {
      if ($role->hasPermission($dimension, $action)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Test whether or not this user is the user currently performing actions.
   *
   * @return True if this is the user that is in this session
   */
  public function isCurrentUser(): bool
  {
    $current = $this->getCurrentUser();
    return (isset($this->id) && $this->id && $this->id === $current->id);
  }
}
