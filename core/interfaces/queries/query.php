<?php
/**
 * Query
 *
 * MySQL query as an object. Allows queries to be built piecemeal with consistency.
 *
 * Base class for specific types of query: SELECT, UPDATE, DELETE, INSERT
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

  /**
   * Construct the query object.
   * Defines the method, table, and verifies the database connection
   *
   * @param string $method SELECT, INSERT, UPDATE, DELETE, etc
   * @param string $table  Table name the query will operate on.
   *
   * @return $this
   */
  public function __construct(string $method, string $table)
  {
    $this->setMethod($method);
    $this->setTable($table);

    $this->connect();
    return $this;
  }

  /**
   * Connect to the database
   *
   * @return $this
   */
  protected function connect()
  {
    $this->conn = new DB();
    return $this;
  }

  /**
   * Setter for the query's method
   *
   * @param string $method SELECT, INSERT, UPDATE, DELETE, etc
   */
  public function setMethod(string $method)
  {
    $this->method = $method;
    return $this;
  }

  /**
   * Setter for the query's table
   * @param string $table  Table name the query will operate on.
   */
  public function setTable(string $table)
  {
    $this->table = $table;
    return $this;
  }

    /**
   * Alias for setTable
   *
   * @param string $table Table Name
   *
   * @return $this
   */
  public function from($table)
  {
    $this->setTable($table);
    return $this;
  }

  /**
   * escape a value for use in the query.
   *
   * Converts TRUE/FALSE to 1/0
   * leave numbers untouched
   * Adds quotes and escape strings
   * Returns NULL as a string for NULL values
   *
   * @param  mixed  $value Un-escaped value
   * @return mixed         Escaped value
   */
  protected function escapeValue(mixed $value)
  {
    if ($value === NULL) {
      return 'NULL';
    } else if ($value === '*') {
      return '*';
    } else if ($value === "'*'") {
      return "'*'";
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
      if (!$this->conn) {
        $this->connect();
      }
      return '"' . $this->conn->real_escape_string($value) . '"';
    } else {
      return 'NULL';
    }
  }

  /**
   * Setter for this query's LIMIT
   *
   * @param int $limit Limit on the number of results
   *
   * @return $this
   */
  public function setLimit(int $limit)
  {
    $this->limit = $limit;
    return $this;
  }

  /**
   * Setter for this query's OFFSET
   *
   * @param int $offset Number of results to skip before starting to return
   *
   * @return $this
   */
  public function setOffset(int $offset)
  {
    $this->offset = $offset;
    return $this;
  }

  /**
   * Add a condition to this query's WHERE clause
   *
   * @param  string $column  Column name
   * @param  string $comp    Comparison operator, eg '=', '<', 'IN'
   * @param  mixed  $value   Value of the column to be included / excluded
   *
   * @return $this
   */
  public function where($column, $comp, $value = NULL)
  {
    
    $last = end($this->whereConditions);
    if ($last && $last !== 'AND' && $last !== 'OR' && $last !== '(') {
      die($last . PHP_EOL . $this . PHP_EOL . 'Where conditions must be joined with AND or OR. found: "' . $last . '"');
    }
    $column = $this->escapeColumn($column);
    switch (trim(strtolower($comp))) {
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
        foreach ($value as $k => $v) {
          if ($k > 0) {
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
        foreach ($value as $k => $v) {
          if ($k > 0) {
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

  /**
   * Add backticks to a column name with or without the table name
   * @param  string $original Original table and/or column name
   * @return string           Table and/or column escaped with backticks.
   */
  protected function escapeColumn(string $original): string
  {
    // wrap the table and/or column name in `backticks`
    if (stristr($original, '.')) {
      $column = '`' . str_replace('.', '`.`', $original) . '`';
    } else {
      $column = "`$original`";
    }
    $column = str_replace('`*`', '*', $column);
    $column = preg_replace('/``/m', '`', $column);
    return $column;
  }

  /**
   * Adds a WHERE condition, following an "AND".
   * The "AND" is only added if there is a preceeding condition.
   *
   * @param  string $column  Column name
   * @param  string $comp    Comparison operator, eg '=', '<', 'IN'
   * @param  mixed  $value   Value of the column to be included / excluded
   *
   * @return $this
   */
  public function and($column = NULL, $comp = NULL, $value = NULL)
  {
    $last = end($this->whereConditions);
    if ($last && $last !== '(') {
      $this->whereConditions[] = 'AND';
    }
    if ($column && $comp) {
      $this->where($column, $comp, $value);
    }
    return $this;
  }

  /**
   * Adds a WHERE condition, following an OR
   * The "OR" is only added if there is a preceeding condition.
   *
   * @param  string $column  Column name
   * @param  string $comp    Comparison operator, eg '=', '<', 'IN'
   * @param  mixed  $value   Value of the column to be included / excluded
   *
   * @return $this
   */
  public function or($column = NULL, $comp = NULL, $value = NULL)
  {
    $last = end($this->whereConditions);
    if ($last && $last !== '(') {
      $this->whereConditions[] = 'OR';
    }
    if ($column && $comp) {
      $this->where($column, $comp, $value);
    }
    return $this;
  }

  /**
   * Adds an AND condition and starts a parenthetical group in the WHERE clause
   *
   * @return $this
   */
  public function andGroup()
  {
    $this->and();
    $this->whereConditions[] = '(';
    return $this;
  }

  /**
   * Adds an OR condition and starts a parenthetical group in the WHERE clause
   *
   * @return $this
   */
  public function orGroup()
  {
    $this->or();
    $this->whereConditions[] = '(';
    return $this;
  }

  /**
   * Closes a parenthetical group in the WHERE clause
   *
   * @return $this
   */
  public function endGroup()
  {
    $this->whereConditions[] = ')';
    return $this;
  }

  /**
   * Add a condition to this query's ORDER BY clause
   * @param  string $column     Column by which to order results
   * @param  string $direction  Direction in which the column will be sorted (ASC / DESC)
   * @return $this
   */
  public function orderBy(string $column, string $direction = 'ASC', bool $escape = true)
  {
    if ($escape){
      $orderColumn = $this->escapeColumn($column);
    } else {
      $orderColumn = $column;
    }
    $condition = "{$orderColumn}";
    if ($direction){
      $condition .= " {$direction}";
    }
    $this->orderConditions[] = $condition;
    return $this;
  }

  /**
   * Format the query as a string
   *
   * eg SELECT * FROM table WHERE column='value' ...
   *
   * @return string Query
   */
  public function __toString()
  {
    $str = $this->method . ' ';

    $str .= " FROM `{$this->table}`";

    if (count($this->joins) > 0) {
      $str .= ' ' . implode(' ', $this->joins);
    }

    $str .= $this->whereString();
    $str .= $this->groupString();
    $str .= $this->orderString();
    $str .= $this->limitString();
    $str .= $this->offsetString();

    return $str;
  }

  /**
   * Format this query's WHERE conditions as a string.
   *
   * @return string WHERE condition1, condition2,
   */
  protected function whereString(): string
  {
    $str = '';
    if (count($this->whereConditions) > 0) {
      $str = ' WHERE ' . implode(' ', $this->whereConditions);
    }
    return $str;
  }

  /**
   * Format this query's GROUP conditions as a string.
   *
   * @return string GROUP BY condition1, condition2,
   */
  protected function groupString(): str
  {
    $str = '';
    if (count($this->groupConditions) > 0) {
      $str = ' GROUP BY ' . implode(', ', $this->groupConditions);
    }
    return $str;
  }

  /**
   * Format this query's ORDER conditions as a string.
   *
   * @return string ORDER BY condition1, condition2,
   */
  protected function orderString(): str
  {
    $str = '';
    if (count($this->orderConditions) > 0) {
      $str = ' ORDER BY ' . implode(', ', $this->orderConditions);
    }
    return $str;
  }

  /**
   * Format this query's LIMIT condition as a string.
   *
   * @return string LIMIT 2
   */
  protected function limitString(): str
  {
    $str = '';
    if (isset($this->limit) && $this->limit) {
      $str = ' LIMIT ' . $this->limit;
    }
    return $str;
  }
  /**
   * Format this query's OFFSET condition as a string.
   *
   * @return string OFFSET 2
   */
  protected function offsetString(): str
  {
    $str = '';
    if (isset($this->offset) && $this->offset) {
      $str = ' OFFSET ' . $this->offset;
    }
    return $str;
  }

  /**
   * Execute this query:
   *  - Get the string value of the query
   *  - Open a connection to the DB
   *  - DB runs the query
   *
   * @return mysqli_result|bool  Returns false on failure.
   *                             For successful queries which produce a result set: returns a mysqli_result object.
   *                             For other successful queries: returns true.
   */
  public function execute(): mixed
  {
    $q = strval($this);
    if (!$this->conn) {
      $this->connect();
    }
    return $this->conn->query($q);
  }
}
