<?php
/**
 * SELECT Query
 *
 * MySQL DELETE query as an object. Allows queries to be built piecemeal with consistency.
 *
 * Example usage:
 *
 * $table = 'users';
 * $id = 375;
 * $q = new DeleteQuery($table)
 *             ->where('id', '=', $id);
 * echo $q;
 * // DELETE  FROM `users` WHERE `id`=375
 *
 * $result = $q->execute();
 * // TRUE
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

class DeleteQuery extends Query
{
  public function __construct($table = NULL)
  {
    $this->setMethod('DELETE', NULL);
    if ($table) {
      $this->from($table);
    }
  }

  /**
   * Format the query as a string
   *
   * eg. DELETE FROM table WHERE column='value'...
   */
  public function __toString():string
  {
    $str = "{$this->method} FROM `{$this->table}`";
    if (count($this->whereConditions) > 0) {
      $str .= ' WHERE ' . implode(' ', $this->whereConditions);
    }
    return $str;
  }
}
