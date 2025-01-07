<?php

namespace Tetra\Database;

require_once('query.php');

class deleteQuery extends Query
{
  public function __construct($table = null)
  {
    $this->setMethod('DELETE', null);
    if ($table) {
      $this->from($table);
    }
  }

  public function from($table)
  {
    $this->setTable($table);
  }

  public function __toString()
  {
    $str = "{$this->method} FROM `{$this->table}`";
    if (count($this->whereConditions) > 0) {
      $str .= ' WHERE ' . implode(' ', $this->whereConditions);
    }
    return $str;
  }
}
