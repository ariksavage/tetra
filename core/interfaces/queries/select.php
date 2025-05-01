<?php
/**
 * SELECT Query
 *
 * MySQL SELECT query as an object. Allows queries to be built piecemeal with consistency.
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
 * // SELECT `name`, `rank`, `serial_no` FROM `personnel` WHERE `rank`='colonel'
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
 * PHP version 8.4
 *
 * @category   Database
 * @package    Core
 * @author     Arik Savage <ariksavage@gmail.com>
 * @version    1.0
 * @since      2025-01-10
 */

namespace Core\Database;

class SelectQuery extends \Core\Database\Query
{
  protected $fields = [];
  protected $joins = [];
  protected $groupConditions = [];

  /**
   * Class constructor
   *
   * @param array  $fields Array of column names to be returned. Default ['*'] for ALL.
   * @param string $table  Table name from which results will be selected.
   *
   * @retrn $this
   */
  public function __construct(array $fields = ['*'], string $table = '')
  {
    parent::__construct('SELECT', $table);
    $this->fields($fields);
    if ($table) {
      $this->from($table);
    }
    return $this;
  }

  /**
   * Add a field to the query, with an optional alias.
   *
   * @param string $field Column name in the database
   * @param string $alias Alias to rename the column in the results
   *
   * @return $this
   */
  public function field(string $column, string $alias = '')
  {
    $field = $this->escapeColumn($column);
    if ($alias) {
      $field .= " AS `$alias`";
    }
    $this->fields[] = $field;

    return $this;
  }

  /**
   * Add a GROUP BY condition to the query.
   *
   * @param  string $field Column to group results by
   *
   * @return $this
   */
  public function groupBy(string $field)
  {
    $this->groupConditions[] = $this->escapeColumn($field);
    return $this;
  }

  /**
   * Add a table join condition to this query
   * @param  string $table    Table name to be joined
   * @param  string $thisCol  Column in the current table to be matched to the new table
   * @param  string $tableCol Column in the new table to be matched to the current table
   * @return $this
   */
  public function leftJoin(string $table, string $thisCol, string $tableCol, string $thisTable = '')
  {
    if (!$thisTable) {
      $thisTable = $this->table;
    }
    $this->joins[] = "LEFT JOIN `{$table}` ON `{$table}`.`{$tableCol}` = `{$thisTable}`.`{$thisCol}`";
    return $this;
  }

  /**
   * Add multiple fields at once to the Query
   *
   * @param Array $fields Fields to be added
   *                      If string keys are present, the key will be used as the column, value as the alias
   *                      If not, values will be used as unaliased column names.
   *
   * @return $this
   */
  public function fields($fields = ['*'])
  {
    if (is_string($fields)) {
      $fields = array($fields);
    }
    foreach ($fields as $key => $value) {
      $alias = '';
      $field = $value;
      // non-numeric keys will be treated as field => $alias
      if (is_string($key)) {
        $alias = $value;
        $field = $key;
      }
      $this->field($field, $alias);
    }
  }

  /**
   * Formats a page number and a number of pages as LIMIT and OFFSET conditions
   *
   * eg page 1, 20 per page
   *   LIMIT 20 OFFSET 0
   *
   * eg page 2, 20 per page
   *   LIMIT 20 OFFSET 20
   *
   * eg page 5, 10 per page
   *   LIMIT 10 OFFSET 40
   *
   * @param  int $page Page number of results to be returned
   * @param  int $per  Number of items to be returned per page
   * @return $this
   */
  public function paginate(int $page, int $per)
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
  public function __toString(): string
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

    if (isset($this->limit)) {
      $str .= ' LIMIT ' . $this->limit;
    }
    if (isset($this->offset)) {
      $str .= ' OFFSET ' . $this->offset;
    }
    return $str;
  }

  /**
   * Get the total number of results of this query, ignoring offset or limit
   *
   * @return int The total number of results.
   */
  public function getTotal(): int
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
   * @param bool   $single If TRUE, only the first object wil be returned.
   * @param string $class  Optional classname for results to be cast into.
   * @param array  $flags  Optional Arguments to be passed to the class on instantiation.
   *
   * @return Array<object> | Object Array of result objects, or a single object.
   */
  public function execute(bool $single = FALSE, string $class = '', array $flags = []): mixed
  {
    $data = FALSE;
    $result = parent::execute();
    if ($result) {
      $data = [];

      while ($obj = $result->fetch_object()) {
        if ($class) {
          $data[] = new $class($obj, $flags);
        } else {
          $data[] = $obj;
        }
      }

      $result->close();
      $this->conn->close();
    }
    if ($single) {
      return reset($data);
    } else {
      return $data;
    }
  }
}
