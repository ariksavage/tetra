<?php
/**
 * SELECT Query
 *
 * MySQL raw query, stored as a string
 * without conditions like the more specific query types
 *
 * PHP version 8.4
 *
 * @category   Database
 * @package    Core
 * @author     Arik Savage <ariksavage@gmail.com>
 * @version    1.0
 * @since      2025-01-10
 */
namespace Core\Database\MySQL\Query;

class Raw extends Base
{
  /**
   * Query string
   * @var string
   */
  public string $string;

  /**
   * Construct the query by storing the raw string.
   * @param string $query Query string
   */
  public function __construct(string $query)
  {
    parent::__construct('', '');
    $this->string = $query;
    return $this;
  }

  /**
   * Overrides default query behavior by returning the raw string.
   * @return string Query string as entered.
   */
  public function __toString()
  {
    return $this->string;
  }

  /**
   * Execute the query
   * @return int|bool Returns the insert_id if available
   *                  Otherwise returns TRUE on success.
   */
  public function executeInsert(): int|bool
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

  public function executeSelect(bool $single = FALSE, string $class = '', array $flags = []): mixed
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
    }
    if ($single) {
      return reset($data);
    } else {
      return $data;
    }
  }
}
