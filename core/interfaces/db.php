<?php
/**
 * Database
 *
 * Database object. Extends the default PHP mysqli class
 *
 * PHP version 8.4
 *
 * @category   Database
 * @package    Core
 * @author     Arik Savage <ariksavage@gmail.com>
 * @version    1.0
 * @since      2025-01-10
 */

namespace Core\Database;

class DB extends \mysqli
{
  use \Core\Common;
  use \Core\Error;

  public function __construct()
  {
    $config = $this->getConfig();
    parent::__construct($config->host, $config->user, $config->password, $config->name);
  }

  /**
   * Get database configuration
   *
   * Loads from the environment, if this is a DDEV project
   * Otherwise loads fro0m a local YAML file.
   *
   * @return object Configuration
   */
  protected function getConfig()
  {
    $config = new \stdClass();
    if (getenv('IS_DDEV_PROJECT')) {
      $config->host = getenv('PGHOST');
      $config->user = getenv('PGUSER');
      $config->password = getenv('PGPASSWORD');
      $config->name = getenv('PGDATABASE');
    } else {
      $tetraConfig = CONFIG_PATH . '/db.config';
      if (file_exists($tetraConfig)) {
        $config = (object) \yaml_parse_file($tetraConfig, 0);
      } else {
        \Tetra\error("Tetra config not found.", "Tetra", 500, ["config_file" => $tetraConfig]);
      }
    }
    return $config;
  }

  /**
   * Test the database connection
   * @return bool TRUE if the database is accessible
   */
  public function test(): bool
  {
    if ($this->connect_error) {
      echo "Not connected, error: " . $this->connect_error;
      return FALSE;
    } else {
       echo "Connected.";
       return TRUE;
    }
  }

  /**
   * Import data from an .sql file
   *
   * @param  string $filePath  Path to file to be imported.
   *
   * @return bool              TRUE on successful import.
   */
  public function importFromFile(string $filePath): bool
  {
    // Temporary variable, used to store current query
    $templine = '';
    // Read in entire file
    $lines = file($filePath);
    // Loop through each line
    foreach ($lines as $line) {
      // Skip it if it's a comment
      if (substr($line, 0, 2) == '--' || $line == '') {
        continue;
      }

      // Add this line to the current segment
      $templine .= $line;
      // If it has a semicolon at the end, it's the end of the query
      if (substr(trim($line), -1, 1) == ';') {
        // Perform the query
        echo $templine . PHP_EOL;
        if (!$this->query($templine)) {
          print('Error performing query \'<strong>' . $templine . '\': ' . mysql_error() . '<br /><br />');
        }
        // Reset temp variable to empty
        $templine = '';
      }
    }
     echo "Tables imported successfully";
     return TRUE;
  }

  /**
   * Add a column to a database table
   * @param string  $table   Table name to be affected
   * @param string  $column  Column name to be created
   * @param string  $type    Column type to be created
   * @param boolean $null    Column allows NULL values
   * @param string  $default Column's default value
   * @param string  $after   Column to be added after.
   */
  public function addColumn(
      string $table,
      string $column,
      string $type,
      bool   $null = TRUE,
      string $default = '',
      string $after = ''
  ) {
    $notNull = !$null ? 'NOT NULL' : '';
    $afterStr = $after ? 'after ' . $after : '';
    $defaultStr = $default ? 'DEFAULT ' . $default : '';
    // Add column if it doesn't already exist.
    $q  = "IF NOT EXISTS( (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()";
    $q .= " AND COLUMN_NAME='%s' AND TABLE_NAME='%s') ) THEN";
    $q .= " ALTER TABLE %s ADD %s %s %s %s %s; END IF;";
    $query = sprintf($q, $column, $table, $table, $column, $type, $notNull, $defaultStr, $afterStr);
    $result = $this->query($query);
    return $result;
  }

  /**
   * Execute a MySQL query
   *
   * @param string $query        The query to be executed.
   * @param int    $resultMode   Constant indicating how the result will be returned from the MySQL server.
   *
   * @return mysqli_result|bool  Returns false on failure.
   *                             For successful queries which produce a result set: returns a mysqli_result object.
   *                             For other successful queries: returns true.
   */
  public function query(string $query, int $resultMode = MYSQLI_STORE_RESULT): \mysqli_result|bool
  {
    try {
      $result = parent::query($query, $resultMode);
      return $result;
    } catch (\mysqli_sql_exception $ex) {
      $this->error($ex->getMessage(), "MySQL", 500, array('query' => $query));
      return FALSE;
    }
  }
}
