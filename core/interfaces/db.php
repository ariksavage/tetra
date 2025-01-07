<?php

namespace Tetra\Database;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

class DB extends \mysqli
{

  public function __construct()
  {
    $tetraConfig = CONFIG_PATH . '/db.config';
    if (file_exists($tetraConfig)){
      $config = (object) \yaml_parse_file($tetraConfig, 0);
    } else {
      \Tetra\error("Tetra config not found.", "Tetra", 500, ["config_file" => $tetraConfig]);
    }
    parent::__construct($config->host, $config->user, $config->password, $config->name);
  }

  public function test() {
    if ($this->connect_error) {
     echo "Not connected, error: " . $this->connect_error;
     return false;
    }
    else {
       echo "Connected.";
       return true;
    }
  }

  public function importFile($filename)
  {
    // Temporary variable, used to store current query
    $templine = '';
    // Read in entire file
    $lines = file($filename);
    // Loop through each line
    foreach ($lines as $line)
    {
    // Skip it if it's a comment
    if (substr($line, 0, 2) == '--' || $line == '')
        continue;

    // Add this line to the current segment
    $templine .= $line;
    // If it has a semicolon at the end, it's the end of the query
    if (substr(trim($line), -1, 1) == ';')
    {
        // Perform the query
        echo $templine . PHP_EOL;
        $this->query($templine) or print('Error performing query \'<strong>' . $templine . '\': ' . mysql_error() . '<br /><br />');
        // Reset temp variable to empty
        $templine = '';
    }
    }
     echo "Tables imported successfully";
  }

  public function addColumn($table, $column, $type, $null = true, $default = null, $after = null)
  {
    $notNull = !$null ? 'NOT NULL' : '';
    $afterStr = $after ? 'after ' . $after : '';
    $defaultStr = $default ? 'DEFAULT ' . $default : '';
    // Add column if it doesn't already exist.
    $q = sprintf("IF NOT EXISTS( (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()
          AND COLUMN_NAME='%s' AND TABLE_NAME='%s') ) THEN
        ALTER TABLE %s ADD %s %s %s %s %s; END IF;", $column, $table, $table, $column, $type, $notNull, $defaultStr, $afterStr);
    $result = $this->query($q);
    return $result;
  }

  public function query(string $query, int $result_mode = MYSQLI_STORE_RESULT): \mysqli_result|bool
  {
    try {
      $result = parent::query($query, $result_mode);
      return $result;
    } catch (\mysqli_sql_exception $ex) {
      \Tetra\error($ex->getMessage(), "MySQL", 500, array('query' => $query));
      return false;
    }
  }
}
