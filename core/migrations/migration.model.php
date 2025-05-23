<?php
/**
 * Migration model
 *
 * Database and filesystem operations to be conducted automatically.
 *
 * PHP version 8.4
 *
 * @category   Model
 * @package    Core
 * @author     Arik Savage <ariksavage@gmail.com>
 * @version    1.0
 * @since      2025-01-14
 */

namespace Core\Migrations\Models;


class Migration extends \Core\Base\Models\Base {
  /**
   * Human friendly name for this migration.
   * @var string
   */
  public string $name = 'migration';

  /**
   * Timestamp at execution start
   * @var int
   */
  protected int $start;

  /**
   * Timestamp at execution end
   * @var int
   */
  protected int $end;

  /**
   * Constructor
   *
   * Start the clock.
   * Check if the migration is already complete
   *
   * If not, execute the migration
   */
  public function __construct()
  {
    $this->start();
    if ($this->isComplete()) {
      $this->end(TRUE);
    } else {
      if ($this->execute()) {
        $this->end();
      }
    }
  }

  /**
   * Track the start time
   * and return a line to the CLI
   *
   * @return void
   */
  protected function start(): void
  {
    $this->start = time();
    $blink = " \033[5m";
    $end = "\033[0m";
    $message = $blink . " Executing..." . $end;
    $this->echoLine($message);
  }

  /**
   * Echo a line of text padded out from the middle with '-'
   *
   * Text will begin with the current migration's name, then '-', and finally the $text
   * Line width is the lesser of the console width and the provided $max
   *
   * @param  string  $text      Text to write on the right end of the line.
   * @param  boolean $overwrite If TRUE, erase the previous line of text in the console before writing.
   * @param  string  $char      Character to repeat between name and $text.
   * @param  int     $max       Line max-length.
   * @return void               Prints text to the console.
   */
  protected function echoLine(string $text, bool $overwrite = FALSE, string $char = '-', int $max = 120)
  {
    $n = 80;
    if (intval(exec('tput cols'))) {
      $n = intval(exec('tput cols'));
    }
    // $n = 80;
    $n = min($n, $max);
    $x = $n - strlen($this->name) - strlen($text);
    if ($overwrite) {
      echo "\e[2A\e[K";
    }
    echo $this->name  . str_repeat('.', $x) . $text . PHP_EOL;
    echo str_repeat($char, $n) . PHP_EOL;
  }

  /**
   * Track the end time of execution
   * and output to CLI if successful or skipped.
   *
   * @param  bool $skipped TRUE if this migration was skipped
   *
   * @return void
   */
  protected function end(bool $skipped = FALSE): void
  {
    $green = "\033[0;32m";
    $nocolor = "\033[0m";
    $this->end = time();

    $message = $green . " COMPLETE";
    if ($skipped) {
      $message .= " (SKIPPED)";
    } else {
      $message .= " (" . $this->elapsed() . 's)';
      $data = array(
        'label' => $this->name
      );
      $this->insert($data, 'migrations')->execute();
    }
    $message .= $nocolor;
    $this->echoLine($message, TRUE);
  }

  /**
   * Test whether this migration is already complete.
   * ie return TRUE if a column has already been created
   *
   * Overridden by each migration
   *
   * @return bool TRUE if the migration is complete
   */
  protected function isComplete(): bool
  {
    return FALSE;
  }

  /**
   * Execute this migration and do whatever is desired.
   *
   * Overridden by each migration
   *
   * @return bool Success of the migration
   */
  protected function execute(): bool
  {
    sleep(4);
    return TRUE;
  }

  /**
   * Get the time of execution in seconds.
   *
   * @return string Time of execution in X.xx s
   */
  protected function elapsed(): string
  {
    $t = (float) $this->end - $this->start;
    return number_format($t, 2, '.', '');
  }

  /**
   * Check if a table exists in the database.
   *
   * @param  string $tableName Name of the table
   *
   * @return bool           TRUE if table exists.
   */
  protected function tableExists(string $tableName): bool
  {
    $q = $this->raw("SHOW TABLES");
    $results = $q->executeSelect();
    $tables = array_map(function($obj) {
      $array = (array)$obj;
      return reset($array);
    }, $results);
    return in_array($tableName, $tables);
  }

  /**
   * Create a table in the database.
   *
   * @param  string $tableName Name of the table
   * @param  array  $columns   Array of columns as MySQL statments
   * @param  array  $key       Array of keys or a single column name as primary key
   *
   * @return bool              TRUE if table was created.
   */
  protected function createTable(string $tableName, array $columns, string|array $key): bool
  {
    $q = "CREATE TABLE `{$tableName}` (";
    $q .= implode(", ", $columns);
    if (is_string($key)) {
      $q .= ", PRIMARY KEY (`{$key}`) )";
    }
    if (is_array($key)) {
      $q .= ', ' . implode(", ", $key) . ")";
    }
    $q .= " ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";
    return $this->raw($q)->execute();
  }

  /**
   * Check if a column exists on a given table
   * @param  String $tableName  Table where the column should exist
   * @param  String $columnName Column name
   * @return bool               TRUE if column exists on table.
   */
  protected function columnExists(string $tableName, string $columnName): bool
  {
    $q = $this->raw("SHOW COLUMNS FROM `{$tableName}` WHERE Field = '{$columnName}'");
    $result = $q->execute();
    return $result->num_rows > 0;
  }

  /**
   * Rename an existing database table
   *
   * @var String $from Old table name
   * @var String $new  New table name
   *
   * @return bool      TRUE if successful.
   */
  protected function renameTable(string $from, string $to): bool
  {
    $q = $this->raw("RENAME TABLE `$from` TO `$to`");
    return $q->execute();
  }

  /**
   * Test if a configuration option exists in the `config` table
   * @param  string $key  Option's key
   * @param  string $type Option's type
   * @return bool         TRUE if the config exists
   */
  protected function configExists(string $key, string $type = 'application'): bool
  {
    $value = $this->select(['*'], 'config')
      ->where('type', '=', $type)
      ->and('key', '=', $key)
      ->execute(TRUE);
    return !!$value;
  }

  /**
   * Create a configuration item in the `config` table.
   *
   * @param  string $key         Option's specific key.
   * @param  string $label       Option's human-readable label.
   * @param  string $type        Option's type (general category).
   * @param  string $description Option's description for users.
   * @param  string $value_type  Option's value type: 'text','longtext','number','bool','object'.
   * @param  mixed  $value       Option's initial value.
   *
   * @return bool                TRUE if the option is created.
   */
  protected function createConfig(
      string $key,
      string $label,
      string $type = 'application',
      string $description = '',
      string $value_type = 'text',
      mixed $value = 'default'
  ) {
    $data = array(
      'label' => $label,
      'description' => $description,
      'type' => $type,
      'key' => $key,
      'value' => $value,
      'value_type' => $value_type
     );
    $q = $this->insert($data, 'config');
    return $q->execute();
  }

  /**
   * Drop a table from the database.
   *
   * @param  string $tableName Name of the table to be dropped.
   *
   * @return bool              TRUE if table is dropped.
   */
  protected function dropTable(string $tableName): bool
  {
    $q = $this->raw("DROP TABLE `$tableName`");
    return $q->execute();
  }

  /**
   * Check if a column is of a specific type.
   *
   * @param  string $tableName  Table to be checked.
   * @param  string $columnName Column to be checked.
   * @param  string $type       Expected type
   *
   * @return bool               TRUE if the column in the table is of the type.
   */
  protected function columnIsType(string $tableName, string $columnName, string $type): bool
  {
    $q = $this->raw("SHOW COLUMNS FROM `{$tableName}` WHERE Field='{$columnName}' and Type like '{$type}%';");
    $result = $q->execute();
    return $result->num_rows > 0;
  }

  /**
   * Add a column to a table
   * @param string $tableName  Table to be altered.
   * @param string $columnName Column name to be added.
   * @param string $type       Column type, eg TINYINT(1) INT(10), VARCHAR(255), TIMESTAMP.
   * @param bool   $null       TRUE if NULL values are allowed.
   * @param string $default    Default value as escaped string.
   * @param string $comment    Optional comment to be added to the column.
   * @param string $after      Column name after which to add this one.
   * @param string $extra      Additional arguments to be added to the query
   *                             eg. "ON UPDATE current_timestamp()"
   *
   * @return bool              Success
   */
  protected function addColumn(
      string $tableName,
      string $columnName,
      string $type = 'VARCHAR(255)',
      bool   $null = TRUE,
      string $default = '',
      string $comment = '',
      string $after = '',
      string $extra = '',
  ): bool {
    $query = "ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$type}";
    if (!$null) {
      $query .= " NOT NULL";
    }
    if ($default || $default === 0) {
      $query .= " DEFAULT {$default}";
    }
    if ($comment) {
      $query .= " COMMENT '{$comment}'";
    }

    if ($extra) {
      $query .= " {$extra}";
    }
    if ($after) {
      $query .= " AFTER `{$after}`";
    }
    return $this->raw($query)->execute();
  }

  protected function addDateCreated(string $tableName)
  {
    return $this->addColumn(
      $tableName,
      'date_created',
      'timestamp',
      FALSE,
      'current_timestamp()',
      '',
      'tenant_id'
    );
  }

  protected function addCreatedBy(string $tableName)
  {
    return $this->addColumn(
      $tableName,
      'created_by',
      'INT(11)',
      FALSE,
      '0',
      '',
      'date_created'
    );
  }

  protected function addDateModified(string $tableName)
  {
    return $this->addColumn(
      $tableName,
      'date_modified',
      'timestamp',
      FALSE,
      'current_timestamp()',
      '',
      'created_by',
      'on update CURRENT_TIMESTAMP'
    );
  }
  protected function dateCreatedToTimestamp(string $tableName)
  {
    return $this->updateColumnIntegerToTimestamp($tableName, 'date_created', FALSE, TRUE, FALSE);
  }
  protected function dateModifiedToTimestamp(string $tableName)
  {
    return $this->updateColumnIntegerToTimestamp($tableName, 'date_modified', FALSE, TRUE, TRUE);
  }

  protected function addModifiedBy(string $tableName)
  {
    return $this->addColumn(
      $tableName,
      'modified_by',
      'INT(11)',
      FALSE,
      '0',
      '',
      'date_modified'
    );
  }

  protected function removeColumn(string $tableName, string $columnName)
  {
    return $this->raw("ALTER TABLE {$tableName} DROP COLUMN {$columnName};")->execute();
  }

  protected function renameColumn(string $tableName, string $old, string $new): bool
  {
    $q = $this->raw("SHOW COLUMNS FROM `{$tableName}` WHERE Field='{$old}';");
    $result = $q->execute();
    $column = $result->fetch_object();
    // var_dump($column);
    $query = "ALTER TABLE `{$tableName}` CHANGE `{$old}` `{$new}` {$column->Type}";
    if ($column->Null == 'NO') {
      $query .= "NOT NULL";
    }

    if ($column->Default) {
      $query .= " DEFAULT " . $column->Default;
    }
    return $this->raw($query)->execute();
  }

  /**
   * Get details about a column's structure
   * @param  string $tableName  Table name where the column is located
   * @param  string $columnName Column name to fetch.
   * @return stdClass           Column data.
   */
  protected function getColumn(string $tableName, string $columnName)
  {
    $q = $this->raw("SHOW COLUMNS FROM {$tableName} WHERE Field='{$columnName}';");
    $result = $q->execute();
    $column = $result->fetch_object();
    return $column;
  }

  protected function updateColumn(
      string $tableName,
      string $columnName,
      string $newName = '',
      string $type = '',
      string $null = '',
      mixed  $default = '',
      string $comment = '',
      string $extra = ''
  ) {
    $column = $this->getColumn($tableName, $columnName);

    // rename column
    if (!$newName) {
      $newName = $columnName;
    }

    // change type
    if (!$type) {
      $type = $column->Type;
    }

    $query = "ALTER TABLE `{$tableName}` CHANGE `{$columnName}` `{$newName}` {$column->Type}";


    // Set null
    if ($null) {
      if ($null == 'NOT NULL') {
        $query .= " $null";
      }
    } else if ($column->Null == 'NO') {
      $query .= " NOT NULL";
    }

    // Set default
    if ($default) {
      $query .= " DEFAULT " . $column->Default;
    } else if ($column->Default) {
      $query .= " DEFAULT " . $column->Default;
    }

    if ($comment) {
      $query .= " COMMENT '{$comment}'";
    }
    if ($extra) {
      $query .= " {$extra}";
    }

    return $this->raw($query)->execute();
  }

  protected function columnAllowsNull($tableName, $columnName)
  {
    $info = $this->getColumn($tableName, $columnName);
    return $info->Null == 'YES';
  }

  protected function columnAllowNull($tableName, $columnName)
  {
    return $this->updateColumn($tableName, $columnName, '', '', 'YES');
  }

  protected function dropColumn(string $tableName, string $columnName): bool
  {
    $query = "ALTER TABLE {$tableName} DROP COLUMN {$columnName};";
    return $this->raw($query)->execute();
  }

  /**
   * Update a column from a unixtime integer into a timestamp.
   *
   * @param  string       $tableName      Table name.
   * @param  string       $columnName     Column name.
   * @param  bool         $allowNull      TRUE if Column can be NULL
   * @param  bool         $defaultCurrent TRUE if column should default to using the current_timestamp()
   * @param  bool|bool    $updateOnUpdate TRUE if column should update to the current_timestamp when a row is updated.
   *
   * @return bool                         TRUE if successful.
   */
  protected function updateColumnIntegerToTimestamp(
      string $tableName,
      string $columnName,
      bool $allowNull = TRUE,
      bool $defaultCurrent = TRUE,
      bool $updateOnUpdate = FALSE
  ): bool {
    // Ensure column is nullable
    $q0 = " ALTER TABLE {$tableName} MODIFY COLUMN {$columnName} Int NULL";

    // Create a temporary column to store the value as a timestamp
    $q1 = "ALTER TABLE {$tableName} ADD COLUMN temp TIMESTAMP AFTER {$columnName}";

    // Store the value in temp, and remove any values from the original column
    $q2  =  "UPDATE {$tableName} SET temp = FROM_UNIXTIME({$columnName})";
    $q2 .= " WHERE {$columnName} IS NOT NULL AND {$columnName} > 300000000";
    $q3 =  "UPDATE {$tableName} SET {$columnName} = NULL;";

    // Update the column to be a timestamp
    $q4 = "ALTER TABLE {$tableName} CHANGE {$columnName} {$columnName} TIMESTAMP";
    if (!$allowNull) {
      $q4 .= " NOT NULL";
    }
    if ($defaultCurrent) {
      $q4 .= " DEFAULT CURRENT_TIMESTAMP";
    }
    if ($updateOnUpdate) {
      $q4 .= " ON UPDATE CURRENT_TIMESTAMP";
    }


    // Copy the temp values back in.
    $q5 = "UPDATE $tableName SET {$columnName} = temp";

    // Delete the temp column.
    $q6 = "ALTER TABLE {$tableName} DROP COLUMN temp";

    $queries = [$q0, $q1, $q2, $q3, $q4, $q5, $q6];
    foreach ($queries as $query) {
      $result = $this->raw($query)->execute();
      if (!$result) {
        return FALSE;
      }
    }
    return TRUE;
  }

  public function permissionExists(string $dimension, string $action = ''): bool
  {
    if ($action == '*') {
      $action = "'*'";
    }
    $query =$this->select()
    ->from('user_permissions')
    ->where('dimension', '=', $dimension)
    ->and('action', '=', $action);
    $permission = $query->execute(FALSE);
    return $permission && count($permission) > 0;
  }

  public function createPermission($dimension, $action, $title = '', $description = ''): int
  {
    $dimensionName = preg_replace('/_+/', ' ', $dimension);
    $dimensionName = ucwords($dimensionName);
    if (!$title) {
      $title = ucwords($action) . ' '. ucwords($dimensionName);
    }
    if (!$description) {
      if (stristr($action, '*')) {
        $description = "User has full access to {$dimensionName}";
      } else {
        $actionLower = strtolower($action);
        $description = "User is allowed to {$actionLower} {$dimensionName}";
      }
    }
    if ($action == '*') {
      $action = "'*'";
    }
    $permission = array(
      'dimension' => $dimension,
      'action' => $action,
      'title' => $title,
      'description' => $description
    );
    $q = $this->insert($permission, 'user_permissions');
    return $q->execute();
  }

  public function assignPermission($permissionId, $roles)
  {
    $roleKeys = "('" . implode("', '", $roles) . "')";
    $query = "INSERT INTO user_role_permissions_assignments (role_id, permission_id)
      SELECT id, $permissionId FROM user_roles WHERE `key` in $roleKeys";
    return $this->raw($query)->executeInsert();
  }

  public function getPermissionId($dimension, $action)
  {
    $permission = $this->select()->from('user_permissions')
    ->where('dimension', '=', $dimension)
    ->and('action', '=', "'$action'")
    ->execute(TRUE);
    if ($permission) {
      return $permission->id;
    } else {
      return $this->createPermission($dimension, $action);
    }
  }

  public function roleHasPermission($role, $dimension, $action)
  {
    $query = "SELECT * FROM `user_role_permissions_assignments` WHERE";
    $roleSubQ = "SELECT `id` FROM `user_roles` WHERE `key`='$role'";
    $query .= " `role_id` IN ($roleSubQ)";
    $permissionSubQ = "SELECT `id` FROM `user_permissions`
    WHERE `action` IN('*', '$action') AND `dimension` = '$dimension'";
    $query .= " AND permission_id IN ($permissionSubQ)";
    $results = $this->raw($query)->executeSelect(FALSE);
    return count($results) > 0;
  }

  public function roleInheritanceExists($role, $inheritsFromRole)
  {
    $query  = "SELECT * FROM user_role_inherit_permissions";
    $query .= " WHERE role_id = (SELECT id FROM user_roles WHERE `key`='$role')";
    $query .= " AND inherits_role = (SELECT id FROM user_roles WHERE `key`='$inheritsFromRole')";
    $results = $this->raw($query)->executeSelect(FALSE);
    return count($results) > 0;
  }

  public function createRoleInheritance($role, $inheritsFromRole)
  {
    $query = "INSERT INTO `user_role_inherit_permissions` (`role_id`, `inherits_role`) VALUES (
      (SELECT `id` FROM `user_roles` WHERE `key`='$role'),
      (SELECT `id` FROM `user_roles` WHERE `key`='$inheritsFromRole')
    )";
    return $this->raw($query)->executeInsert();
  }

  public function describeTableProperties(string $tableName)
  {
    echo PHP_EOL;
    echo PHP_EOL;
    $results = $this->raw("SHOW COLUMNS FROM {$tableName}")->execute();
    while ($column = $results->fetch_object()) {
      $label = preg_replace('/_/', ' ', $column->Field);
      $label = trim(ucfirst($label));
      switch (TRUE) {
        case stristr($column->Type, 'tinyint'):
          $type = 'bool';
          $default = "FALSE";
          break;
        case stristr($column->Type, 'int'):
        case stristr($column->Type, 'decimal'):
          $type = 'int';
          $default = "0";
          break;
        case stristr($column->Type, 'enum'):
        case stristr($column->Type, 'varchar'):
        case stristr($column->Type, 'text'):
        case stristr($column->Type, 'blob'):
          $type = 'string';
          $default = "''";
          break;
        case stristr($column->Type, 'timestamp'):
          $type = 'int';
          $default = "0";
          break;
        default:
          var_dump($column);
          die();
          break;
      }
      echo "/**" . PHP_EOL;
      echo " * {$label}" . PHP_EOL;
      echo " * @var {$type}" . PHP_EOL;
      echo " */" . PHP_EOL;
      echo "public {$type} \${$column->Field} = {$default};" . PHP_EOL;
      echo PHP_EOL;
    }

    echo PHP_EOL;
    echo PHP_EOL;
  }
}
