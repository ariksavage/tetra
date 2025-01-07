<?php

namespace Tetra\Database;

require_once(__dir__ . '/../db.php');

class Query
{
  protected $method;
  protected $table;
  protected $conn;
  protected $whereConditions = [];
  protected $limit;
  protected $offset;
  protected $orderConditions = [];

  public function __construct($method, $table)
  {
    $this->setMethod($method);
    $this->setTable($table);

    $this->connect();
  }

  protected function connect() {
    $tetraConfig = CONFIG_PATH . '/tetra.config';
    if (file_exists($tetraConfig)){
      $coreConfig = (object) \yaml_parse_file(CONFIG_PATH . '/tetra.config', 0);
      $config = (object) $coreConfig->db;
    } else {
      \Tetra\error("Tetra config not found.", "Tetra", 500, ["config_file" => $tetraConfig]);
    }

    // add db
    $this->conn = new db(
      $config->host,
      $config->user,
      $config->password,
      $config->name
    );
  }

  public function setMethod($method = null)
  {
    $this->method = $method;
  }

  public function setTable($table)
  {
    $this->table = $table;
    return $this;
  }

  protected function escapeValue($value) {
    if ($value === NULL) {
      return 'NULL';
    } else if ($value === FALSE) {
      return 0;
    } else if ($value === TRUE) {
      return 1;
    } else if (($value || $value === 0 ) && is_numeric($value)) {
      return $value;
    } else if (is_object($value) || is_array($value)) {
      $value = json_encode($value);
      return '"' . $this->conn->real_escape_string($value) . '"';
    } else if ($value && stristr($value, '()')) {
      return $value;
    } else if (($value || $value === '') && is_string($value)) {
      if (!$this->conn){
        $this->connect();
      }
      return '"' . $this->conn->real_escape_string($value) . '"';
    } else {
      return 'NULL';
    }
    echo $this . PHP_EOL;
  }

  public function setLimit($limit)
  {
    $this->limit = $limit;
  }

  public function setOffset($offset)
  {
    $this->offset = $offset;
  }

  public function where($column, $comp, $value = null)
  {
    
    $last = end($this->whereConditions);
    if ($last && $last !== 'AND' && $last !== 'OR' && $last !== ' (') {
      die($last . PHP_EOL . $this . PHP_EOL . 'Where conditions must be joined with AND or OR. found: "' . $last . '"');
    }
    // wrap the column name in `backticks`
    if (stristr($column, '.')) {
      $column = str_replace('.', '.`', $column) . '`';
    } else {
      $column = "`$column`";
    }
    switch(trim(strtolower($comp))) {
      case 'is null':
        $condition ="{$column} IS NULL";
        break;
      case 'not null':
        $condition ="{$column} IS NOT NULL";
        break;
      case 'not in query':
        $subQuery = $value;
        $condition = "{$column} NOT IN ({$subQuery})";
        break;
      case 'not in':
        $group = '(';
        $value = array_values($value);
        foreach($value as $k => $v){
          if ($k > 0){
            $group .= ', ';
          }
          $group .= $this->escapeValue($v);
        }
        $group .= ')';
        $condition ="{$column} NOT IN {$group}";
        break;
      case 'in query':
        $subQuery = $value;
        $condition = "{$column} IN ({$subQuery})";
        break;
      case 'in':
        $group = '(';
        $value = array_values($value);
        foreach($value as $k => $v){
          if ($k > 0){
            $group .= ', ';
          }
          $group .= $this->escapeValue($v);
        }
        $group .= ')';
        $condition ="{$column} IN {$group}";
        break;
      case 'empty':
        $condition ="({$column} IS NULL OR {$column} <> '' OR {$column} <> ' ')";
        break;
      case 'not_empty':
        $condition ="({$column} IS NOT NULL AND {$column} <> '' AND {$column} <> ' ')";
        break;
      case 'contains':
        $value = $this->escapeValue("%{$value}%");
        $condition = "{$column} LIKE {$value}";
        break;
      case 'not contains':
        $value = $this->escapeValue("%{$value}%");
        $condition = "{$column} NOT LIKE {$value}";
        break;
      case 'starts_with':
        $value = $this->escapeValue("{$value}%");
        $condition = "{$column} LIKE {$value}";
        break;
      case 'ends_with':
      $value = $this->escapeValue("%{$value}");
        $condition = "{$column} LIKE {$value}";
        break;
      default:
        $value = $this->escapeValue($value);
        $condition = "{$column}{$comp}{$value}";
        break;
    }
    if ($condition && !in_array($condition, $this->whereConditions)) {
      $this->whereConditions[] = $condition;
    }
    return $this;
  }

  public function and()
  {

    $last = end($this->whereConditions);
    if (!$last) {
      // Skip the and, since there are no preceeding conditions
      return $this;
    }
    if ($last && ($last == 'AND' || $last == 'OR')) {
      die($this . PHP_EOL . ' AND must follow another condition');
    }
    $this->whereConditions[] = 'AND';
    return $this;
  }

  public function or()
  {
    $last = end($this->whereConditions);
    if (!$last) {
      // Skip the or, since there are no preceeding conditions
      return $this;
    }
    if ($last && ($last == 'AND' || $last == 'OR')) {
      die($this . PHP_EOL . ' OR must follow another condition');
    }
    $this->whereConditions[] = 'OR';
    return $this;
  }

  public function andGroup()
  {
    $this->and();
    $this->whereConditions[] = ' (';
    return $this;
  }

  public function orGroup()
  {
    $this->or();
    $this->whereConditions[] = ' (';
    return $this;
  }

  public function endGroup()
  {
    $this->whereConditions[] = ')';
    return $this;
  }

  public function andIsAnyOf($column, $comparison, $values = []) {
      $this->andGroup();
      foreach($values as $i => $value) {
        if ($i > 0){
          $this->or();
        }
        $this->where($column, $comparison, $value);
      }
      $this->endGroup();
    return $this;
  }
  public function andIsNoneOf($column, $comparison, $values) {
    $this->andGroup();
    $this->where($column, 'is null');
    
    if ($values){
      $this->orGroup();
      foreach($values as $i => $value) {
        if ($i > 0){
          $this->and();
        }
        $this->where($column, $comparison, $value);
      }
      $this->endGroup();
    }
    $this->endGroup();
    return $this;
  }

  public function orderBy($column, $direction = 'ASC')
  {
    $this->orderConditions[] = "{$column} {$direction}";
    return $this;
  }

  public function execute() {
    $q = strval($this);
    if (!$this->conn) {
      $this->connect();
    }
    return $this->conn->query($q);
  }
}
