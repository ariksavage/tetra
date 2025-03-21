<?php
/**
 * SELECT Query
 *
 * MySQL UPDATE query as an object. Allows queries to be built piecemeal with consistency.
 *
 * Example usage:
 *
 * $table = 'users';
 * $data = ['first_name' => 'Jeremy'];
 * $id = 375;
 * $q = new UpdateQuery($table)
 *     ->set($data)
 *     ->where('id', '=', $id);
 * echo $q;
 * // UPDATE users SET `first_name`='Jeremy' WHERE `id`=375
 *
 * $result = $q->execute();
 * // 1
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

class UpdateQuery extends Query
{
  /**
   * Data as $column => $value
   * with which to update the database
   *
   * @var array
   */
  protected $data;

  /**
   * Class constructor. set method = 'UPDATE'
   * and initialize $data.
   *
   * @param string $table Table name to be updated.
   */
  public function __construct(string $table = '')
  {
    parent::__construct('UPDATE', $table);
    $this->data = [];
    return $this;
  }

  /**
   * Set $data for this query
   * @param array $data Array of [$column => $value]
   */
  public function set(array $data)
  {
    $this->data = $data;
    return $this;
  }

  /**
   * Format the query as a string
   *
   * eg. UPDATE users SET `first_name`='Jeremy' WHERE `id`=375 ...
   */
  public function __toString(): string
  {
    if (!$this->data) {
      $this->error("No data found.");
    }

    $str = "{$this->method} `{$this->table}` SET";

    $i = 0;
    foreach ($this->data as $column => $value) {
      $column = $this->escapeColumn($column);
      $value = $this->escapeValue($value);
      if ($i > 0) {
        $str .= ',';
      }
      $str .= " $column = $value";
      $i++;
    }

    $str .= $this->whereString();
    return $str;
  }

  /**
   * Execute the query, and return the number of rows affected
   *
   * @return int Number of rows affected
   */
  public function execute(): int
  {
    $result = parent::execute();
    $updated = $this->conn->affected_rows;
    return $updated;
  }
}
