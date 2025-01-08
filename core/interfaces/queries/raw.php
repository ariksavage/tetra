<?php

namespace Core\Database;

require_once('query.php');

class rawQuery extends Query
{
  public $string;

  public function __construct($string)
  {
    parent::__construct(null, null);
    $this->string = $string;
  }
  public function __toString()
  {
    return $this->string;
  }

  public function execute(){
    return parent::execute();
  }
}
