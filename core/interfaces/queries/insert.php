<?php

namespace Core\Database;

require_once('query.php');

class insertQuery extends Query
{
  protected $data;

  public function __construct($data)
  {
    parent::__construct('INSERT', null);
    unset($data['id']);
    $this->data = $data;
  }

  public function into($table)
  {
    $this->setTable($table);
    return $this;
  }

  public function __toString()
  {
    $str = "{$this->method} INTO `{$this->table}`";
    $columns = array_keys($this->data);
    $columns = '`' . implode('`, `', $columns) . '`';
    $values = array_map(array($this, 'escapeValue'), $this->data);
    $values = implode(', ', $values);
    $str .= " ($columns) VALUES ($values)";
    // $str .= " ON DUPLICATE KEY UPDATE";
    // $i = 0;
    // foreach($this->data as $k => $v) {
    //  if ($i > 0) {
    //    $str .= ",";
    //  }
    //  $str .= " `$k`=" . $this->escapeValue($v);
    //  $i++;
    // }
    return $str;
  }

  public function execute() {
    if($result = parent::execute()) {
      if ($this->conn->insert_id) {
        return $this->conn->insert_id;
      } else {
        return true;
      }
    }
  }
}
