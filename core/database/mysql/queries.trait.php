<?php
namespace Core\Database\MySQL\Traits;

use \Core\Database\MySQL\Query\Select as selectQuery;
use \Core\Database\MySQL\Query\Insert as insertQuery;
use \Core\Database\MySQL\Query\Update as updateQuery;
use \Core\Database\MySQL\Query\Delete as deleteQuery;
use \Core\Database\MySQL\Query\Raw as rawQuery;

trait Queries {

  /**
   * Create a SELECT query.
   * @param  array  $fields Fields to select from the DB. Default: All ('*').
   * @param  String $table  Table name to select from.
   *
   * @return selectQuery    Query object
   */
  protected function select($fields = ['*'], string $table = '')
  {
    if (!$table && $this->table) {
      $table = $this->table;
    }
    $q = new selectQuery($fields, $table);

    return $q;
  }

  /**
   * Create an UPDATE query. See interfaces\queries
   *
   * @param  array  $data [$key => $value] array of data to update.
   * @param  String $table  Table name to update.
   *
   * @return selectQuery    Query object
   */
  protected function update(array $data, string $table = '')
  {
    if (!$table && $this->table) {
      $table = $this->table;
    }
    $q =  new updateQuery($table);
      $q->set($data);
    return $q;
  }

  /**
   * Create an INSERT query. See interfaces\queries
   * 
   * @param  array  $data [$key => $value] array of data to insert.
   * @param String $table Table into which data will be inserted.
   * 
   * @return insertQuery $query Query Object.
   */
  protected function insert($data, string $table = '')
  {
    if (!$table && isset($this->table)) {
      $table = $this->table;
    }
    $query = new insertQuery($data);
    if ($table) {
      $query->into($table);
    }
    return $query;
  }

  /**
   * Create a DELETE query. See interfaces\queries
   *
   * @param String $table Table into which data will be inserted.
   * @param  integer $id ID of the item to be deleted (if provided)
   *
   * @return insertQuery $query Query Object.
   */
  protected function deleteQuery($table = '', $id = null)
  {
    if (!$table && isset($this->table)) {
      $table = $this->table;
    }
    $query = new deleteQuery($table);
    if ($id) {
      $query->where('id', '=', $id);
    }
    return $query;
  }

  /**
   * Create a raw Query object.
   * @param  String $query A MySQL Query string
   * @return rawQuery      Query object
   */
  protected function raw($query)
  {
    return new rawQuery($query);
  }

  /**
   * Get descriptions of all columns in a given table.
   * @param  String $table Table name
   * @return Object        Object including `name` and `columns`
   */
  public function describeTable($table = null)
  {
    if (!$table && isset($this->table)){
      $table = $this->table;
    }

    if (!isset($_SESSION['schemas'])) {
      $_SESSION['schemas'] = Array();
    }

    if (isset($_SESSION['schemas'][$table])) {
      return $_SESSION['schemas'][$table];
    } else {
      $schema = new \stdClass();
      $schema->name = $table;
      $schema->columns = Array();
      $response = $this->raw("DESCRIBE $table")->execute();
      while($column = $response->fetch_object()) {

        $column->name = $column->Field;
        unset($column->Field);

        $type = $column->Type;
        unset($column->Type);
        preg_match_all('/^([a-z\_]+)\(*([0-9]*)\)* *(.*)/m', $type, $typeMatches, PREG_SET_ORDER, 0);
        if (isset($typeMatches[0])){
          $column->type = $typeMatches[0][1] ?? null;
          $column->length = $typeMatches[0][2] ?? null;
          $column->unsigned = ($typeMatches[0][3] ?? null) == 'unsigned';
        }

        $column->allowNull = $column->Null !== 'NO';
        unset($column->Null);

        $column->isKey = !!$column->Key;
        unset($column->Key);

        $column->default = $column->Default;
        unset($column->Default);

        $column->extra = $column->Extra;
        unset($column->Extra);

        $schema->columns[$column->name] = $column;
      }

      $_SESSION['schemas'][$table] = $schema;
      return $schema;
    }
  }
}
