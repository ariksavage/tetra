<?php

namespace Tetra\Database;

require_once('query.php');

/**
 * @class
 * The select object represents a SELECT query in MySQL.
 * 
 * Example usage:
 * 
 * $fields = ['name', 'rank', 'serial_no'];
 * $table = 'personnel';
 * $column = 'rank';
 * $value = 'colonel';
 * $q = new select($fields)
 *             ->from($table)
 *             ->where($column, '=', $value);
 * echo $q;
 * // SELECT name, rank, serial_no FROM personnel WHERE rank='colonel'
 * 
 * $colonels = $q->execute();
 * [
 *     {"name": "Wilhelm Klink", "rank": "colonel", "serial_no": 1975},
 *     {"name": "Harland Sanders", "rank": "colonel", "serial_no": 11}
 * ]
 * 
 * or all in one, and select a single record:
 * 
 * $colonels = $q = new select($fields)
 *             ->from($table)
 *             ->where($column, '=', $value)
 *             ->execute(true);
 * 
 * {"name": "Wilhelm Klink", "rank": "colonel", "serial_no": 1975}
 * 
 */
class selectQuery extends Query
{
  protected $fields = [];
  protected $joins = [];
  protected $groupConditions = [];

  public function __construct($fields = ['*'], $table = null)
  {
    parent::__construct('SELECT', null);
    $this->fields($fields);
    if ($table) {
      $this->from($table);
    }
  }

  /**
   * Alias to set the table for the query
   * 
   * @param String $table Table Name
   * 
   * @return select $this Return the object to allow chaining
   */
  public function from($table)
  {
    $this->setTable($table);
    return $this;
  }

  /**
   * Add a field to the query, with an optional alias.
   * 
   * @param String $field Column name in the database
   * @param String $alias Alias to rename the column in the results
   * 
   * @return select $this Return the object to allow chaining
   */
  public function field($field, $alias = null) {
    if ($alias) {
      $this->fields[] = "$field AS $alias"; 
    } else {
      $this->fields[] = $field;
    }
    return $this;
  }

  public function groupBy($field) {
    $this->groupConditions[] = $field;
    return $this;
  }

  public function leftJoin($table, $thisCol, $tableCol)
  {
    $this->joins[] = "LEFT JOIN {$table} ON {$table}.{$tableCol} = {$this->table}.{$thisCol}";
    return $this;
  }

  /**
   * Add multiple fields at once to the Query
   * 
   * @param Array $fields Fields to be added
   *                      If string keys are present, the key will be used as the column, value as the alias
   *                      If not, values will be used as unaliased column names.
   * 
   * @return select $this Return the object to allow chaining
   */
  public function fields($fields = ['*'])
  {
    if (is_string($fields)) {
      $fields = array($fields);
    }
    foreach($fields as $key => $value) {
      $alias = null;
      $field = $value;
      // non-numeric keys will be treated as field => $alias
      if (is_string($key)) {
        $alias = $value;
        $field = $key;
      }
      $this->field($field, $alias);
    }
  }

  public function paginate($page, $per)
  {
    $this->setLimit($per);
    $this->setOffset(($page - 1) * $per);
    return $this;
  }

  /**
   * Format the query as a string
   * 
   * eg SELECT * FROM table WHERE column='value' ...
   */
  public function __toString()
  {
    $str = $this->method . ' ';
    $str .= implode(', ', $this->fields);
    $str .= " FROM {$this->table}";
    if (count($this->joins) > 0) {
      $str .= ' ' . implode(' ', $this->joins);
    }
    if (count($this->whereConditions) > 0) {
      $str .= ' WHERE ' . implode(' ', $this->whereConditions);
    }

    if (count($this->groupConditions) > 0) {
      $str .= ' GROUP BY ' . implode(', ', $this->groupConditions);
    }

    if (count($this->orderConditions) > 0) {
      $str .= ' ORDER BY ' . implode(', ', $this->orderConditions);
    }

    if(isset($this->limit)) {
      $str .= ' LIMIT ' . $this->limit;
    }
    if(isset($this->offset)) {
      $str .= ' OFFSET ' . $this->offset;
    }
    return $str;
  }

  public function getTotal()
  {
    $q  = $this->method . ' ';
    $q .= 'count(*) AS total';
    $q .= " FROM {$this->table}";
    if (count($this->whereConditions) > 0) {
      $q .= ' WHERE ' . implode(' ', $this->whereConditions);
    }
    $result = $this->conn->query($q);
    $total = $result->fetch_object()->total;
    $result->close();
    return intval($total);
  }

  /**
   * Execute the query and return an array of objects, or a single object
   * 
   * @param Boolean $single If true, only the first object wil be returned.
   * 
   * @return Array<object> | Object Array of result objects, or a single object.
   */
  public function execute($single = false, $class = null, $flags = []) {
    $data = false;
    $result = parent::execute();
    if ($result) {
      $data = [];

          while($obj = $result->fetch_object()) {
            if ($class){
              $data[] = new $class($obj, $flags);
            } else {
              $data[] = $obj;
            }
      }
    }
    $result->close();
    if ($single) {
      return reset($data);
    } else {
      return $data;
    }
  }
}
