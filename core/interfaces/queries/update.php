<?php

namespace Core\Database;

require_once('query.php');

class updateQuery extends Query
{
  protected $data;
  public function __construct($table)
  {
    parent::__construct('UPDATE', null);
    $this->setTable($table);
  }

  public function set($data)
  {
    $this->data = $data;
    return $this;
  }

  public function __toString()
  {
    if (!$this->data) {
      \Tetra\error('no data to update');
      return false;
    }

    $str = "{$this->method} {$this->table} SET";
    
    $i = 0;
    foreach($this->data as $column => $value) {
      $value = $value = $this->escapeValue($value);
      if ($i > 0) {
        $str .= ',';
      }
      $str .= " $column = $value";
      $i++;
    }
    
    if (count($this->whereConditions) > 0) {
      $str .= ' WHERE ' . implode(' ', $this->whereConditions);
    }
    return $str;
  }

  public function execute()
  {
    $result = parent::execute();
    $updated = $this->conn->affected_rows;
    return $updated;
  }
}
