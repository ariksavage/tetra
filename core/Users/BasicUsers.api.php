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
  /**
   * The user list. This API doesn't require a login as a whole (password
   * reset and the current user need to work without one), so each method
   * checks its own permission.
   */
  public function listGET($flags = [])
  {
    $this->requirePermission('users', 'VIEW');
    // Each user's role IDs, for the people screen's Roles column.
    $userRoles = [];
    foreach ($this->select(['user_id', 'role_id'])->from('users_roles')->execute() ?: [] as $row) {
      $userRoles[intval($row->user_id)][] = intval($row->role_id);
    }
    $this->addData('user_roles', (object) $userRoles);
    parent::listGET($flags);
  }

  /**
   * ?role= and ?status= narrow the list, as well as ?search=.
   */
  protected function searchQueryFilter(selectQuery &$query): selectQuery
  {
    parent::searchQueryFilter($query);
    if ($roleId = $this->getValue('role', 0, 'integer')) {
      $query->and('id', 'IN QUERY', 'SELECT `user_id` FROM `users_roles` WHERE `role_id` = ' . intval($roleId));
    }
    if ($status = $this->getValue('status', '', 'string')) {
      $query->and('status', '=', $status);
    }
    if (!$this->getValue('orderBy', '', 'string')) {
      $query->orderBy('username');
    }
    return $query;
  }

  /**
   * A user for the admin screens, with the IDs of their roles.
   *
   * GET /core/users/account/{id}
   */
  public function accountGET(int $userId)
  {
    $this->requirePermission('users', 'VIEW');
    $user = $this->getById($userId);
    $this->addData('role_ids', $this->roleIds($userId));
    $this->success('user', $user);
  }

  /**
   * Create or update a user from the admin screens: their details, status,
   * roles and, optionally, a new password.
   *
   * POST /core/users/account { "user": {...}, "role_ids": [1, 2], "password": "" }
   */
  public function accountPOST()
  {
    $data = $this->postValue('user', NULL, 'object');
    $isNew = empty($data->id);
    $this->requirePermission('users', $isNew ? 'CREATE' : 'EDIT');
    $roleIds = array_values(array_unique(array_map('intval', (array) $this->postValue('role_ids', []))));
    $password = strval($this->postValue('password', '', 'string'));

    $user = new User();
    if (!$isNew) {
      $user->byId(intval($data->id));
    }
    foreach (['username', 'email', 'first_name', 'last_name', 'status'] as $field) {
      if (isset($data->$field)) {
        $user->$field = trim(strval($data->$field));
      }
    }
    if (!$user->username || !$user->email || !$user->first_name || !$user->last_name) {
      $this->error('A user needs a username, email address, first name and last name.', 400, 'Validation');
    }
    if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
      $this->error("That email address doesn't look right.", 400, 'Validation');
    }
    $user->status = strtolower($user->status ?: 'active');
    if (!in_array($user->status, ['active', 'pending', 'deactivated'], TRUE)) {
      $this->error('Unknown status: ' . $user->status, 400, 'Validation');
    }
    $clash = $this->select(['id', 'username', 'email'])->from('users')
      ->where('id', '!=', intval($user->id ?? 0))
      ->andGroup()->where('username', '=', $user->username)->or('email', '=', $user->email)->endGroup()
      ->execute(TRUE);
    if ($clash) {
      $what = strcasecmp($clash->username, $user->username) === 0 ? 'username' : 'email address';
      $this->error("Another account already uses that {$what}.", 409, 'Conflict');
    }
    if ($isNew && strlen($password) < 8) {
      $this->error('A new user needs a password of at least 8 characters.', 400, 'Validation');
    }
    if ($password && strlen($password) < 8) {
      $this->error('Passwords need at least 8 characters.', 400, 'Validation');
    }

    // Guard against locking yourself out.
    if (!$isNew && $this->isMe($user)) {
      if ($user->status !== 'active') {
        $this->error("You can't deactivate your own account.", 400, 'Validation');
      }
      if (!$this->rolesStillAdminister($roleIds)) {
        $this->error("Those roles would take away your own access to manage users. Ask another administrator to change your roles.", 400, 'Validation');
      }
    }

    if ($password) {
      $user->setPassword($password);
    }
    if (!$user->save()) {
      $this->error('Could not save the user.', 500);
    }
    // Roles can only be given out by someone who may edit roles; others keep what they had.
    if ($this->currentUserCan('users', 'EDIT')) {
      $roles = $this->select(['id'])->from('roles')->where('id', 'IN', $roleIds ?: [0])->execute();
      $user->updateRoles(array_map(fn($r) => intval($r->id), $roles ?: []));
    }
    $this->addData('role_ids', $this->roleIds(intval($user->id)));
    $this->success('user', $this->getById(intval($user->id)), ($isNew ? 'Created ' : 'Saved ') . $user->name() . '.');
  }

  /**
   * Delete a user. You can't delete yourself.
   */
  public function idDELETE(int $id)
  {
    $this->requirePermission('users', 'DELETE');
    $user = $this->getById($id);
    if ($this->isMe($user)) {
      $this->error("You can't delete your own account.", 400, 'Validation');
    }
    $name = $user->name();
    if ($user->delete()) {
      $this->successMsg("Deleted {$name}.");
    }
  }

  /** @return int[] */
  protected function roleIds(int $userId): array
  {
    $rows = $this->select(['role_id'])->from('users_roles')->where('user_id', '=', $userId)->execute() ?: [];
    return array_map(fn($r) => intval($r->role_id), $rows);
  }

  protected function isMe($user): bool
  {
    $me = $this->getCurrentUser();
    return $me && intval($me->id) === intval($user->id);
  }

  protected function currentUserCan(string $dimension, string $action): bool
  {
    $me = $this->getCurrentUser();
    return $me && $me->hasPermission($dimension, $action);
  }

  /**
   * Whether a set of roles, including the roles they inherit, still lets
   * someone manage users.
   */
  protected function rolesStillAdminister(array $roleIds): bool
  {
    foreach ($roleIds as $roleId) {
      $role = new UserRole();
      $role->byId($roleId, FALSE);
      if ($role->id && $role->hasPermission('users', 'EDIT')) {
        return TRUE;
      }
    }
    return FALSE;
  }

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
   * The current user's preferences, as key => value.
   *
   * @param  int $userId User ID
   *
   * @return \stdClass
   */
  protected function loadPreferences(int $userId): \stdClass
  {
    $preferences = new \stdClass();
    $rows = $this->select()->from('user_preferences')->where('user_id', '=', $userId)->execute(FALSE);
    foreach ($rows ?: [] as $row) {
      $key = $row->key;
      $preferences->$key = json_decode($row->value ?? 'null');
    }
    return $preferences;
  }

  /**
   * Get the current user's preferences.
   * Anonymous visitors have none stored here; the app keeps theirs in the browser.
   *
   * @return never JSON preferences
   */
  public function preferencesGET()
  {
    $user = $this->getCurrentUser();
    $preferences = $user ? $this->loadPreferences(intval($user->id)) : new \stdClass();
    $this->success('preferences', $preferences);
  }

  /**
   * Save one or more of the current user's preferences.
   * Only the keys that are sent are changed; a null value removes the preference.
   *
   * POST: { "preferences": { "keep_screen_on": true } }
   *
   * @return never JSON preferences, after saving
   */
  public function preferencesPOST()
  {
    $user = $this->getCurrentUser();
    if (!$user) {
      $this->error('Login required', 401);
    }
    $userId = intval($user->id);
    $changes = $this->postValue('preferences');
    if (!is_object($changes)) {
      $this->error('No preferences were sent.', 422, 'Preferences');
    }

    foreach ($changes as $key => $value) {
      if (!is_string($key) || !preg_match('/^[a-z0-9_]{1,64}$/', $key)) {
        $this->error("Invalid preference name: {$key}", 422, 'Preferences');
      }
      $json = json_encode($value);
      if (strlen($json) > 60000) {
        $this->error("Preference {$key} is too large.", 422, 'Preferences');
      }

      $existing = $this->select()->from('user_preferences')
        ->where('user_id', '=', $userId)
        ->and('key', '=', $key)
        ->execute(TRUE);

      if ($value === NULL) {
        if ($existing) {
          $this->deleteQuery('user_preferences', intval($existing->id))->execute();
        }
      } else if ($existing) {
        $this->update(['value' => $json], 'user_preferences')->where('id', '=', intval($existing->id))->execute();
      } else {
        $this->insert(['user_id' => $userId, 'key' => $key, 'value' => $json], 'user_preferences')->execute();
      }
    }

    $this->success('preferences', $this->loadPreferences($userId));
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
      // Not logged in, or the token no longer matches a session.
      $this->error('Not logged in', 401);
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
    $this->requirePermission('users', 'EDIT');
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
    $this->requirePermission('users', 'EDIT');
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
    $this->requirePermission('users', 'EDIT');
    $user = $this->getById($userId);
    $roles = $this->postValue('roles');

    $success = $user->updateRoles($roles);
    if ($success) {
      $this->successMsg("User ({$userId}) roles successfully updated");
    }
  }

  /** How long an emailed reset link keeps working. */
  const RESET_EMAIL_MINUTES = 60;

  /** Send at most one reset email per user this often. */
  const RESET_EMAIL_EVERY_MINUTES = 5;

  /**
   * The address reset links point to: the site's own (Site address,
   * base_url), never one from the request, which anyone could set to their
   * own site to receive a working link. A local copy in DDEV may use the
   * local address it's open at (localhost, 127.0.0.1 or *.ddev.site).
   *
   * @param string $requested The address the app is open at
   * @return string The origin, or '' if the site's address isn't set
   */
  protected function resetOrigin(string $requested = ''): string
  {
    $requested = rtrim($requested, '/');
    if (getenv('IS_DDEV_PROJECT') === 'true'
      && preg_match('#^https?://(localhost|127\.0\.0\.1|[a-z0-9-]+(\.[a-z0-9-]+)*\.ddev\.site)(:\d+)?$#i', $requested)) {
      return $requested;
    }
    return rtrim(strval($this->configValue('base_url') ?? ''), '/');
  }

  /**
   * Make a password reset link for a user, to send them yourself. It works
   * for the Password reset expires (days) setting, once.
   * @param  int    $userId User ID
   * @return never          JSON response includes link and text.
   */
  public function passwordResetLinkGET(int $userId)
  {
    $this->requirePermission('users', 'EDIT');
    $user = $this->getById($userId);
    $origin = $this->resetOrigin($this->getValue('host', '', 'string'));
    if (!$origin) {
      $this->error("Set the site's address (Site address, on the Configuration page) to make reset links.", 400, 'Validation');
    }
    $expiresDays = intval($this->configValue('password_reset_expires_days')) ?: 10;
    $this->data->link = $user->passwordResetLink($origin, $expiresDays * 24 * 60);
    $this->data->text  = "Copy the link below to send to the user.";
    $this->data->text .= " It works once, for <strong>{$expiresDays}</strong> days.";
    $this->success();
  }

  /**
   * Forgot password: email a reset link to the user with this username or
   * email address (login). Answers the same whether or not there is one, so
   * it can't be used to find out who has an account.
   */
  public function forgotPasswordPOST(): never
  {
    $login = trim($this->postValue('login', '', 'string'));
    $answer = 'If that matches an account, a link to set a new password is on its way to its email address. It works for an hour.';
    if (!$login) {
      $this->error('Enter your username or email address.', 400, 'Validation');
    }
    $user = $this->select()->from('users')
      ->where('username', '=', $login)->or('email', '=', $login)
      ->execute(TRUE, '\Core\Users\Models\User');
    if ($user && $user->status === 'active' && $user->email && !$user->recentPasswordReset(self::RESET_EMAIL_EVERY_MINUTES)) {
      $origin = $this->resetOrigin($this->postValue('host', '', 'string'));
      if (!$origin) {
        error_log("Tetra: no reset email for user {$user->id}: the site's address (base_url) isn't set.");
      } else {
        $this->sendResetEmail($user, $user->passwordResetLink($origin, self::RESET_EMAIL_MINUTES));
      }
    }
    $this->success('message', $answer);
  }

  protected function sendResetEmail($user, string $link): void
  {
    $appName = htmlspecialchars(strval($this->configValue('name')));
    $name = htmlspecialchars($user->name());
    $href = htmlspecialchars($link);
    $lines = [
      "<p>Hello {$name},</p>",
      "<p>Someone asked to set a new password for your {$appName} account. If it was you, use this link within an hour:</p>",
      "<p><a href=\"{$href}\">Set a new password</a></p>",
      "<p>If it wasn't you, ignore this email: your password stays as it is.</p>",
    ];
    if (!$user->message("Set a new password for {$appName}", implode("\r\n", $lines))) {
      error_log("Tetra: the reset email to user {$user->id} could not be sent.");
    }
  }

  /**
   * Whether a reset link is still good, so the page can say so before
   * asking for a new password: ?user_id=&token=
   */
  public function passwordResetCheckGET(): never
  {
    $this->success('valid', $this->resetUser()?->validResetToken($this->getValue('token', '', 'string')) ?? FALSE);
  }

  /**
   * Set a new password with a reset link: { user_id, token, password,
   * password2 }. Then log in with it as usual.
   */
  public function passwordResetPOST(): never
  {
    $password = $this->postValue('password', '', 'string');
    if (strlen($password) < 8) {
      $this->error('Passwords need at least 8 characters.', 400, 'Validation');
    }
    if ($password !== $this->postValue('password2', '', 'string')) {
      $this->error("The passwords don't match.", 400, 'Validation');
    }
    $user = $this->resetUser(TRUE);
    if (!$user || !$user->resetPasswordWithToken($this->postValue('token', '', 'string'), $password)) {
      $this->error('This link has expired or been used. Ask for a new one.', 400, 'Validation');
    }
    $this->success('username', $user->username, 'Your new password is set. Log in with it.');
  }

  /** The user a reset link names, or NULL. */
  protected function resetUser(bool $posted = FALSE)
  {
    $userId = $posted ? $this->postValue('user_id', 0, 'integer') : $this->getValue('user_id', 0, 'integer');
    if ($userId < 1) {
      return NULL;
    }
    return $this->select()->from('users')->where('id', '=', $userId)->and('status', '=', 'active')
      ->execute(TRUE, '\Core\Users\Models\User') ?: NULL;
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
    $this->requirePermission('users', 'EDIT');
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
    $this->requirePermission('users', 'VIEW');
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
    $this->requirePermission('users', 'VIEW');
    $category = $this->getCategoryById($catId, ['USER_COUNT']);
    $this->success('category', $category);
  }

  /**
   * Save or Update a user category.
   * @return never             JSON updated
   */
  public function categoryPOST()
  {
    $this->requirePermission('users', 'EDIT');
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
    $this->requirePermission('users', 'EDIT');
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
    $this->requirePermission('users', 'VIEW');
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
    $this->requirePermission('users', 'VIEW');
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
    $this->requirePermission('users', 'EDIT');
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
    $this->requirePermission('users', 'EDIT');
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
    $this->requirePermission('users', 'EDIT');
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
    $this->requirePermission('users', 'EDIT');
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
      $this->reqPermission('EDIT OWN');
    } else {
      $this->reqPermission('EDIT');
    }
    $success = $model->save();
    if ($success) {
      $this->success($this->singular, $model, "Successfully saved {$this->singular}.");
    } else {
      $this->error("Could not save {$this->singular}.", $this->plural, $code = 500, $data);
    }
  }

  /**
   * Update a user's email address
   * 
   * Requires verification of a security token to ensure the email address is actually accessible.
   */

  public function emailPOST(): never
  {
    $data = $this->postValue('user');
    $user = $this->getById($data->id);
    if (!isset($data->security)){
            $token = $user->generateSecurity(true, $data->email_address);
      $this->sendEmail($data->email_address, "Confirm email address change", "<p>Use this code to confirm your change of email address.</p><p><strong>{$token}</strong></p>");
      $this->successMsg("Check your new email to confirm");
    } else if ($user->checkSecurity($data->security)) {
      $user->email = $data->email_address;
      if ($user->save()){
        $this->successMsg('Successfully updated email to ' . $data->email_address);
      } else {
        $this->error("Unable to update email address");
      }
    }
  }
}
?>
