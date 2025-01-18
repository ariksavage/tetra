<?php

namespace Core\Database;

require_once(__dir__ . '/../db.php');
require_once(CORE_ROOT . '/error.trait');

class Query
{
  protected $method;
  protected $table;
  protected $conn;
  protected $whereConditions = [];
  protected $limit;
  protected $offset;
  protected $orderConditions = [];

  use \Core\Error;

  public function __construct($method, $table)
  {
    $this->setMethod($method);
    $this->setTable($table);

    $this->connect();
  }

  protected function connect() {
    // add db
    $this->conn = new DB();
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
    if ($last && $last !== 'AND' && $last !== 'OR' && $last !== '(') {
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

  public function and($column = null, $comp = null, $value = null)
  {
    $last = end($this->whereConditions);
    if ($last && $last !== '(') {
      $this->whereConditions[] = 'AND';
    }
    if ($column && $comp){
      $this->where($column, $comp, $value);
    }
    return $this;
  }

  public function or($column = null, $comp = null, $value = null)
  {
    $last = end($this->whereConditions);
    if ($last && $last !== '(') {
      $this->whereConditions[] = 'OR';
    }
    if ($column && $comp){
      $this->where($column, $comp, $value);
    }
    return $this;
  }

  public function andGroup()
  {
    $this->and();
    $this->whereConditions[] = '(';
    return $this;
  }

  public function orGroup()
  {
    $this->or();
    $this->whereConditions[] = '(';
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
        $this->of($column, $comparison, $value);
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
        $this->and($column, $comparison, $value);
      }
      $this->endGroup();
    }
    $this->endGroup();
    return $this;
  }

  public function orderBy($column, $direction = 'ASC')
  {
    $this->orderConditions[] = "`{$column}` {$direction}";
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
