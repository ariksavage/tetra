<?php
/**
 * INSERT Query
 *
 * MySQL INSERT query as an object. Allows queries to be built piecemeal with consistency.
 *
 * Example usage:
 *
 * $table = 'users';
 * $data = ['first_name' => 'Jeremy'];
 * $q = new InsertQuery($data)
 *     ->into($table);
 *
 * echo $q;
 * // INSERT INTO users (`first_name`) VALUES ('jeremy')
 *
 * $result = $q->execute();
 * // 104
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


class InsertQuery extends Query
{
  /**
   * Array of [$column => $value]
   * Data to be inserted
   * @var array
   */
  protected array $data;

  /**
   * If TRUE adds " ON DUPLICATE KEY UPDATE" to the query.
   * @var boolean
   */
  protected bool $_updateOnDuplicate = FALSE;

  /**
   * Class constructor. Sets the data to be inserted
   *
   * @param array $data [description]
   *
   * @return $this
   */
  public function __construct(array $data)
  {
    parent::__construct('INSERT', '');
    if (isset($data['id']) && !$data['id']) {
      unset($data['id']);
    }
    $this->data = $data;
  }

  /**
   * Alias for setTable
   *
   * @param  string $table Table name
   *
   * @return $this
   */
  public function into(string $table)
  {
    $this->setTable($table);
    return $this;
  }

  public function updateOnDuplicate()
  {
    $this->_updateOnDuplicate = TRUE;
    return $this;
  }

  /**
   * Format the query as a string
   *
   * eg. INSERT INTO users (`first_name`) VALUES ('jeremy') ...
   */
  public function __toString()
  {
    $str = "{$this->method} INTO `{$this->table}`";
    $columns = array_keys($this->data);
    $columns = array_map(array($this, 'escapeColumn'), $columns);
    $columns = implode(', ', $columns);
    $values = array_map(array($this, 'escapeValue'), $this->data);
    $values = implode(', ', $values);
    $str .= " ($columns) VALUES ($values)";

    if ($this->_updateOnDuplicate) {
      $str .= " ON DUPLICATE KEY UPDATE";
      $i = 0;
      foreach ($this->data as $k => $v) {
        if ($i > 0) {
          $str .= ", ";
        }
        $str .=  $this->escapeColumn($k) . "=" . $this->escapeValue($v);
        $i++;
      }
    }
    return $str;
  }

  /**
   * Execute the query
   * @return int|bool Returns the insert_id if available
   *                  Otherwise returns TRUE on success.
   */
  public function execute(): int|bool
  {
    if ($result = parent::execute()) {
      if ($this->conn->insert_id) {
        return $this->conn->insert_id;
      } else {
        return TRUE;
      }
    }
    return FALSE;
  }
}
