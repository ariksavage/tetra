<?php
/**
 * Courses API
 *
 * Manage Installation
 *
 * PHP version 8.4
 *
 * @category   API
 * @package    Core
 * @author     Arik Savage <ariksavage@gmail.com>
 * @version    1.0
 * @since      2025-01-22
 */
namespace Core\Install\API;

use \Core\Database\MySQL\DB\MySQL as DB;

use \Core\Users\Models\User;
use \Core\Config\Models\Config;

/**
 * Class Install
 *
 * Handles all operations involved in in the initial installation of the system.
 */
class Install extends \Core\Base\API\Base {

  public function __construct()
  {
    $this->data = new \stdClass();
  }

  /**
   * Check if authorization is required for this API.
   * May be overridden to allow public access. Not recommended.
   *
   * @return Boolean TRUE if login is required
   */
  public function requiresAuth(): bool
  {
    return FALSE;
  }

  protected function installKeyFile(): string
  {
    return CONFIG_PATH . '/.install';
  }

  protected function writeInstallKey($key): bool
  {
    if (file_put_contents($this->installKeyFile(), $key)) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  protected function readInstallKey(): string
  {
    $key = '';
    if (file_exists($this->installKeyFile())) {
      $key = file_get_contents($this->installKeyFile());
    }
    return $key;
  }

  protected function testInstallKey(): void
  {
    if (function_exists('apache_request_headers')) {
      $headers = \apache_request_headers();
      $providedKey = $headers['Installkey'] ?? NULL;
      $realKey = $this->readInstallKey();
      if($providedKey !== $realKey) {
        $this->error('Invalid key. Delete the existing `config/.install` file to re-start the installation.', 401, "Invalid install key");
      }
    }
  }


public function testGET(){
  $this->success('hello');
}

  public function databasePOST()
  {
    $host = $this->postValue('host');
    $user = $this->postValue('user');
    $password = $this->postValue('password');
    $name = $this->postValue('name');

    $data = [
      'host' => $host,
      'user' => $user,
      'password' => $password,
      'name' => $name,
    ];

    $file = CONFIG_PATH . '/db.config';

    // Convert array to YAML
    $yaml = yaml_emit($data, YAML_UTF8_ENCODING);
    // Remove leading '---' and trailing '...'
    $yaml = preg_replace('/^---\s*\n/', '', $yaml);
    $yaml = preg_replace('/\n\.\.\.\s*$/', '', $yaml);

    // Write to file
    if (file_put_contents($file, $yaml) === false) {
      throw new RuntimeException("Failed to write configuration file: {$file}");
    }

    // Test DB
    $db = new DB();
    $test = $db->test();
    if (!$test){
      unlink($file);
      $this->error("Invalid database credentials");
    } else {
      $bytes = random_bytes(32); // 32 bytes = 64 hex characters
      $installKey = bin2hex($bytes);
      if ($this->writeInstallKey($installKey)){
        $this->success("key", $installKey, "Database credentials have been saved.");
      } else {
        $this->error('could not write key');
      }
    }
  }

  /**
   * Create the Users table.
   * This table handles user accounts
   *
   * @return void Success Message
   */
  public function usersTablePUT(): void
  {
    $this->testInstallKey();
    $tableName = 'users';
    $columns = [
      // name
      "`username` varchar(255) NOT NULL",
      "`email` varchar(255) NOT NULL",
      "`name_prefix` varchar(255) DEFAULT NULL",
      "`first_name` varchar(255) NOT NULL",
      "`middle_name` varchar(255) DEFAULT NULL",
      "`last_name` varchar(255) NOT NULL",
      "`name_suffix` varchar(255) DEFAULT NULL",
      // account
      "`password` varchar(255) NOT NULL",
      "`security` varchar(255) DEFAULT NULL",
      "`status` enum('active','pending','deactivated') DEFAULT 'active'",
      "`change_password` tinyint(1) NOT NULL DEFAULT 0",
      "`notify_activation` int(11) NOT NULL DEFAULT 0",
      "`profile_pic_uri` varchar(255) DEFAULT NULL",
      // Address
      "`phone_number` varchar(45) DEFAULT NULL",
      "`address_1` varchar(255) DEFAULT NULL",
      "`address_2` varchar(45) DEFAULT NULL",
      "`city` varchar(45) DEFAULT NULL",
      "`state` varchar(45) DEFAULT NULL",
      "`zip` varchar(45) DEFAULT NULL",
      "`country` varchar(128) DEFAULT NULL",
      "`date_of_birth` timestamp NULL DEFAULT NULL",
      "`lang` enum('af','ar','ar_AE','ar_BH','ar_DZ','ar_EG','ar_IQ','ar_JO','ar_KW','ar_LB','ar_LY','ar_MA','ar_OM','ar_QA','ar_SA','ar_SY','ar_TN','ar_YE','as','ast','az','be','bg','bn','br','bs','ca','ce','ch','co','cr','cs','cv','cy','da','de','de_AT','de_CH','de_DE','de_LI','de_LU','el','en','en_AU','en_BZ','en_CA','en_GB','en_IE','en_JM','en_NZ','en_PH','en_TT','en_US','en_ZA','en_ZW','eo','es','es_AR','es_BO','es_CL','es_CO','es_CR','es_DO','es_EC','es_ES','es_GT','es_HN','es_MX','es_NI','es_PA','es_PE','es_PR','es_PY','es_SV','es_UY','es_VE','et','eu','fa','fa_IR','fi','fj','fo','fr','fr_BE','fr_CA','fr_CH','fr_FR','fr_LU','fr_MC','fur','fy','ga','gd','gd_IE','gl','gu','he','hi','hr','hsb','ht','hu','hy','id','is','it','it_CH','iu','ja','ji','ka','kk','km','kn','ko','ko_KP','ko_KR','ks','ky','la','lb','lt','lv','mi','mk','ml','mo','mr','ms','mt','my','nb','ne','ng','nl','nl_BE','nn','no','nv','oc','om','or','pa','pa_IN','pa_PK','pl','pt','pt_BR','qu','rm','ro','ro_MO','ru','ru_MO','sa','sb','sc','sd','sg','si','sk','sl','so','sq','sr','sv','sv_FI','sv_SV','sw','sx','sz','ta','te','th','tig','tk','tlh','tn','tr','ts','tt','uk','ur','ve','vi','vo','wa','xh','zh','zh_CN','zh_HK','zh_SG','zh_TW','zu') DEFAULT 'en_US'"
    ];
    $keys = [
      "UNIQUE KEY `username_UNIQUE` (`username`)",
      "UNIQUE KEY `primary_email_UNIQUE` (`email`)"
    ];
    $this->createTable($tableName, $columns, $keys);
  }

  /**
   * Create the Roles table.
   * This table handles user roles
   *
   * @return void Success Message
   */
  public function rolesTablePUT()
  {
    $this->testInstallKey();
    $tableName = 'roles';
    $columns = [
      "`key` varchar(255) DEFAULT NULL",
      "`label` varchar(255) DEFAULT NULL",
      "`description` blob DEFAULT NULL",
      "`default` tinyint(1) NOT NULL COMMENT 'Role is added to any new users by default'",
    ];
    $keys = [
      "UNIQUE KEY `title` (`label`)"
    ];
    $this->createTable($tableName, $columns, $keys);
  }

  /**
   * Create the Role Inheritance table.
   * This table handles which eroles inherit permissions from which other roles.
   *
   * @return void Success Message
   */
  public function rolesInheritanceTablePUT(): void
  {
    $this->testInstallKey();
    $tableName = 'roles_inheritance';
    $columns = [
      "`role_id` int(11) DEFAULT NULL",
      "`inherits_role` int(11) DEFAULT NULL"
    ];
    $keys = [
      "UNIQUE KEY `Role combination` (`role_id`,`inherits_role`)"
    ];
    $this->createTable($tableName, $columns, $keys, FALSE, FALSE);
  }

  /**
   * Create the Permissions table.
   * This table handles permissions for individual actions
   *
   * @return void Success Message
   */
  public function permissionsTablePUT(): void
  {
    $this->testInstallKey();
    $tableName = 'permissions';
    $columns = [
      "`dimension` varchar(255) NOT NULL",
      "`action` enum('*','VIEW','VIEW OWN','CREATE','EDIT','EDIT OWN','DELETE','DELETE OWN') NOT NULL",
      "`title` varchar(255) NOT NULL",
      "`description` blob DEFAULT NULL"
    ];
    $keys = [
      "UNIQUE KEY `dimension/action` (`dimension`,`action`)"
    ];
    $this->createTable($tableName, $columns, $keys);
  }

  /**
   * Create the Roles Permissions table.
   * This table controls role/permission assignments
   *
   * @return void Success Message
   */
  public function rolesPermissionsTablePUT(): void
  {
    $this->testInstallKey();
    $tableName = 'roles_permissions';
    $columns = [
      "`role_id` int(11) NOT NULL",
      "`permission_id` int(11) NOT NULL"
    ];
    $keys = [
      "PRIMARY KEY (`role_id`,`permission_id`)"
    ];
    $this->createTable($tableName, $columns, $keys, TRUE, FALSE);
  }

  /**
   * Create the Migrations table.
   * This table handles user accounts
   *
   * @return void Success Message
   */
  public function migrationsTablePUT(): void
  {
    $this->testInstallKey();
    $tableName = 'migrations';
    $columns = [
      // name
      "`label` varchar(255) NOT NULL",
      "`completed` timestamp NOT NULL DEFAULT current_timestamp()"
    ];
    $keys = [
      "UNIQUE KEY `label_UNIQUE` (`label`)"
    ];
    $this->createTable($tableName, $columns, $keys);
  }

  /**
   * Create default roles and permissions
   * @return [type] [description]
   */
  public function defaultRolesPermissionsPUT()
  {
    $this->testInstallKey();
    // Check roles don't already exist
    $role = $this->select()->from('roles')->where('key', '=', 'root')->execute(TRUE);
    if ($role) {
      $this->successMsg("Default roles and permissions already exist.");
    }
    // Create roles
    $rootRole = [
      'key' => 'root',
      'label' => 'Root',
      'description' => 'Top level user with all available permissions',
      'default' => false,
    ];
    $root = $this->insert($rootRole, 'roles')->execute();
    $adminRole = [
      'key' => 'admin',
      'label' => 'Admin',
      'description' => 'Application administrator',
      'default' => false,
    ];
    $admin = $this->insert($adminRole, 'roles')->execute();
    $userRole = [
      'key' => 'user',
      'label' => 'User',
      'description' => 'General User',
      'default' => true,
    ];
    $user = $this->insert($userRole, 'roles')->execute();

    // create inheritances
    $this->insert(["role_id" => $root, "inherits_role" => $admin], 'roles_inheritance')->execute();
    $this->insert(["role_id" => $admin, "inherits_role" => $user], 'roles_inheritance')->execute();

    // Define default permissions
    $permissions = [
      // Users
      [
        'dimension' => 'users',
        'action' => 'CREATE',
        'title' => 'Create User Account',
        'description' => 'Allows creating a new user account',
        'role' => NULL,
      ],
      [
        'dimension' => 'users',
        'action' => 'VIEW',
        'title' => 'View User Account',
        'description' => 'Allows viewing any user account',
        'role' => NULL,
      ],
      [
        'dimension' => 'users',
        'action' => 'VIEW OWN',
        'title' => 'View Own User Account',
        'description' => 'Allows viewing your own user account',
        'role' => $user,
      ],
      [
        'dimension' => 'users',
        'action' => 'EDIT',
        'title' => 'Edit User Account',
        'description' => 'Allows editing any user account',
        'role' => NULL,
      ],
      [
        'dimension' => 'users',
        'action' => 'EDIT OWN',
        'title' => 'Edit Own User Account',
        'description' => 'Allows editing your own user account',
        'role' => $user,
      ],
      [
        'dimension' => 'users',
        'action' => 'DELETE',
        'title' => 'Delete User Account',
        'description' => 'Allows deleting any user account',
        'role' => NULL,
      ],
      [
        'dimension' => 'users',
        'action' => 'DELETE OWN',
        'title' => 'Delete Own User Account',
        'description' => 'Allows deleting your own user account',
        'role' => NULL,
      ],
      [
        'dimension' => 'users',
        'action' => "'*'",
        'title' => 'All User Permissions',
        'description' => 'Grants all permissions related to user accounts',
        'role' => $admin,
      ],

      // Config
      [
        'dimension' => 'config',
        'action' => 'CREATE',
        'title' => 'Create Configuration',
        'description' => 'Allows creating new configuration entries',
        'role' => NULL,
      ],
      [
        'dimension' => 'config',
        'action' => 'VIEW',
        'title' => 'View Configuration',
        'description' => 'Allows viewing any configuration entry',
        'role' => NULL,
      ],
      [
        'dimension' => 'config',
        'action' => 'EDIT',
        'title' => 'Edit Configuration',
        'description' => 'Allows editing any configuration entry',
        'role' => NULL,
      ],
      [
        'dimension' => 'config',
        'action' => 'DELETE',
        'title' => 'Delete Configuration',
        'description' => 'Allows deleting any configuration entry',
        'role' => NULL,
      ],
      [
        'dimension' => 'config',
        'action' => "'*'",
        'title' => 'All Configuration Permissions',
        'description' => 'Grants all permissions related to configurations',
        'role' => $admin,
      ],

      // Permissions
      [
        'dimension' => 'permissions',
        'action' => 'CREATE',
        'title' => 'Create Permission',
        'description' => 'Allows creating new permissions',
        'role' => NULL,
      ],
      [
        'dimension' => 'permissions',
        'action' => 'VIEW',
        'title' => 'View Permission',
        'description' => 'Allows viewing any permission',
        'role' => NULL,
      ],
      [
        'dimension' => 'permissions',
        'action' => 'EDIT',
        'title' => 'Edit Permission',
        'description' => 'Allows editing any permission',
        'role' => NULL,
      ],
      [
        'dimension' => 'permissions',
        'action' => 'DELETE',
        'title' => 'Delete Permission',
        'description' => 'Allows deleting any permission',
        'role' => NULL,
      ],
      [
        'dimension' => 'permissions',
        'action' => "'*'",
        'title' => 'All Permission Permissions',
        'description' => 'Grants all permissions related to permissions',
        'role' => $admin,
      ],

      // Roles
      [
        'dimension' => 'roles',
        'action' => 'CREATE',
        'title' => 'Create Role',
        'description' => 'Allows creating new roles',
        'role' => NULL,
      ],
      [
        'dimension' => 'roles',
        'action' => 'VIEW',
        'title' => 'View Role',
        'description' => 'Allows viewing any role',
        'role' => NULL,
      ],
      [
        'dimension' => 'roles',
        'action' => 'EDIT',
        'title' => 'Edit Role',
        'description' => 'Allows editing any role',
        'role' => NULL,
      ],
      [
        'dimension' => 'roles',
        'action' => 'DELETE',
        'title' => 'Delete Role',
        'description' => 'Allows deleting any role',
        'role' => NULL,
      ],
      [
        'dimension' => 'roles',
        'action' => "'*'",
        'title' => 'All Role Permissions',
        'description' => 'Grants all permissions related to roles',
        'role' => $admin,
      ],
    ];

    // Loop to insert
    foreach ($permissions as $permission) {
      // Extract role value
      $roleId = $permission['role'] ?? NULL;

      // Remove role before inserting into permissions table
      $permissionData = $permission;
      unset($permissionData['role']);



      $permissionId = $this->insert($permissionData, 'permissions')->execute();

      if ($roleId !== NULL) {
        $this->insert(["role_id" => $roleId, "permission_id" => $permissionId], 'roles_permissions')->execute();
      }
    }

    $this->successMsg("Default roles and permissions have been created.");
  }
  /**
   * Create the User Sessions table
   * This table handles user login sessions
   *
   * @return void Success Message
   */
  public function usersSessionsTablePUT(): void
  {
    $this->testInstallKey();
    $tableName = 'users_sessions';
    $columns = [
      "`user_id` int(11) NOT NULL",
      "`token` varchar(255) NOT NULL",
      "`date_started` timestamp NULL DEFAULT current_timestamp()",
      "`date_last_access` timestamp NULL DEFAULT current_timestamp()",
      "`date_expires` timestamp NOT NULL",
      "`date_ended` timestamp NULL DEFAULT NULL"
    ];
    $keys = ["PRIMARY KEY (`token`)"];
    $this->createTable($tableName, $columns, $keys, FALSE, FALSE);
  }

  /**
   * Create the Users Roles table
   * This table handles user/role assignments
   *
   * @return void Success Message
   */
  public function usersRolesTablePUT(): void
  {
    $this->testInstallKey();
    $tableName = 'users_roles';
    $columns = [
      "`user_id` int(11) NOT NULL",
      "`role_id` int(11) NOT NULL"
    ];
    $keys = [
      "PRIMARY KEY (`user_id`,`role_id`)"
    ];
    $this->createTable($tableName, $columns, $keys, TRUE, FALSE);
  }

  /**
   * Create the User Groups table.
   * This table controls individual User Groups
   *
   * @return void Success Message
   */
  public function userGroupsTablePUT(): void
  {
    $this->testInstallKey();
    $tableName = 'user_groups';
    $columns = [
      "`label` varchar(255) NOT NULL",
      "`description` varchar(255) DEFAULT NULL",
      "`public` tinyint(1) NOT NULL DEFAULT 0",
    ];
    $keys = [
      "UNIQUE KEY `unique_label` (`label`)"
    ];
    $this->createTable($tableName, $columns, $keys);
  }

  /**
   * Create the Users Groups table.
   * This table controls user/group assignments
   *
   * @return void Success Message
   */
  public function usersGroupsTablePUT(): void
  {
    $this->testInstallKey();
    $tableName = 'users_groups';
    $columns = [
      "`user_id` int(11) NOT NULL",
      "`group_id` int(11) NOT NULL"
    ];
    $keys = [
      "PRIMARY KEY (`user_id`, `group_id`)",
      "UNIQUE KEY `user-group` (`user_id`,`group_id`)"
    ];
    $this->createTable($tableName, $columns, $keys, FALSE, FALSE);
  }

  /**
   * Create the Root User
   * @return void JSON User
   */
  public function rootUserPOST(): void
  {
    $this->testInstallKey();
    $userData = $this->postValue('root_user');

    // check user doesn't already exist
    $existing = $this->select()
                  ->from('users')
                  ->where('username', '=', $userData->username)
                  ->or('email', '=', $userData->email)
                  ->execute(TRUE);
    if ($existing) {
      $this->successMsg('User ' . $existing->username . " already exists.");
    }

    $user = new \Core\Users\Models\User($userData);
    $user->status = 'active';
    $user->save();
    $rootRole = $this->select()->from('roles')->where('key', '=', 'root')->execute(true);
    $user->updateRoles([$rootRole]);
    $user->save();
    $this->success('user', $user, "{$user->name()} has been created.");
  }

  /**
   * Create the Tags table.
   * This table controls tags to be applied to other entities
   *
   * @return void Success Message
   */
  public function tagsTablePUT(): void
  {
    $this->testInstallKey();
    $tableName = 'tags';
    $columns = [
      "`label` varchar(255) NOT NULL",
      "`category` varchar(255) NOT NULL",
      "`description` blob"
    ];
    $keys = [];
    $this->createTable($tableName, $columns, $keys);
    $this->successMsg("{$tableName} has been created");
  }

  /**
   * Check if the given table currently exists.
   *
   * @param string $tableName Name of the table to look for
   *
   * @return void JSON Response {"data": {"exists": TRUE} }
   */
  public function tableExistsGET(string $tableName): void
  {
    $this->testInstallKey();
    $exists = $this->tableExists($tableName);
    $this->success('exists', $exists);
  }

  /**
   * Check if the default roles have been created
   *
   * @return void JSON Response {"data": {"exists": TRUE} }
   */
  public function hasDefaultRolesGET(): void
  {
    $this->testInstallKey();
    $exists = false;
    if ($this->tableExists('roles')){
      $roles = $this->select()->from('roles')->execute();
      $exists = count($roles) >1;
    }
    $this->success('exists', $exists);
  }

  /**
   * Check if there is an existing root user
   *
   * @return void JSON Response {"data": {"exists": TRUE} }
   */
  public function hasRootUserGET(): void
  {
    $this->testInstallKey();
    $exists = false;
    if ($this->tableExists('roles') && $this->tableExists('users_roles') ){
      $role = $this->select()->from('roles')->where('key', '=', 'root')->execute(TRUE);
      $usersQ = $this->select()->from('users_roles')->where('role_id', '=', intval($role->id));
      $users = $usersQ->execute();
      $exists =  count($users) > 0;
    }
    $this->success('exists',$exists);
  }

  /**
   * Create the Tags table.
   * This table controls tags to be applied to other entities
   *
   * @return void Success Message
   */
  public function tagsAssignmentsTablePUT(): void
  {
    $this->testInstallKey();
    $tableName = 'tags_assignments';
    $columns = [
      "`entity_type` varchar(255) NOT NULL",
      "`entity_id` int(10) unsigned NOT NULL",
      "`tag_id` int(10) unsigned NOT NULL"
    ];
    $keys = [
      "PRIMARY KEY (`entity_type`,`entity_id`,`tag_id`)",
      "KEY `entity_type-entity_id` (`entity_type`,`entity_id`)"
    ];
    $this->createTable($tableName, $columns, $keys, TRUE, FALSE);
  }

  /**
   * Create the Config table.
   * This table handles application configuration
   *
   * @return void Success Message
   */
  public function configTablePUT(): void
  {
    $this->testInstallKey();
    $tableName = 'config';
    $columns = [
      "`label` varchar(255) DEFAULT NULL",
      "`description` blob DEFAULT NULL",
      "`type` varchar(255) NOT NULL DEFAULT 'application'",
      "`key` varchar(255) NOT NULL",
      "`value` varchar(255)",
      "`value_type` enum('text','longtext','number','boolean','object') DEFAULT 'text'"
    ];
    $keys = [
      "UNIQUE KEY (`type`,`key`)"
    ];
    $this->createTable($tableName, $columns, $keys);
  }

  /**
   * Create base configuration
   *
   * Nothing to create since 2026-11-13: the application settings (name,
   * copyright, session lengths) come from the app's tetra.json and Tetra's
   * defaults (tetra.schema.json). A config row would mean "this site
   * overrides it", which a fresh install doesn't. Kept so the install steps
   * still complete.
   */
  public function defaultConfigPUT(): void
  {
    $this->testInstallKey();
    $this->successMsg("Configuration comes from tetra.json; nothing to create.");
  }

  public function configGET(): void
  {
    $this->testInstallKey();
    $config = $this->select()->from('config')->where('type', '=', 'application')->execute(FALSE, '\Core\Config\Models\Config');
    $this->success('config', $config);
  }

  public function configPOST(): void
  {
    $this->testInstallKey();
    $config = $this->postValue('config');
    foreach($config as $item) {
      $configItem = new \Core\Config\Models\Config($item);
      $configItem->save();
    }
    $this->successMsg('Configuration updated');

  }

  /**
   * Check if the default configuration has been created.
   *
   * @return void JSON Response {"data": {"exists": TRUE} }
   */
  /*
  public function hasDefaultConfigGET(): void
  {
    $this->testInstallKey();
    $exists = false;
    if ($this->tableExists('config')){
      $config = $this->select()->from('config')->where('key', '=', 'session_expires_days')->execute(TRUE);
      $exists = !!$config;
    }
    $this->success('exists', $exists);
  }
  */
  /**
   * Check if configuration has been updated.
   *
   * @return void JSON Response {"data": {"exists": TRUE} }
   */
  /*
  public function configUpdatedGET(): void
  {
    $this->testInstallKey();
    $config = $this->select()->from('config')->where('key', '=', 'name')->and('type', '=', 'application')->execute(TRUE);
    $this->success('exists', !!$config->value);
  }
  */
}
