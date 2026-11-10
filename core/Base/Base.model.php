<?php
/**
 * Base model class
 *
 * Represents an object that can be READ, UPDATED, DELETED
 *
 * PHP version 8.4
 *
 *
 * @category   Model
 * @package    Core
 * @author     Arik Savage <ariksavage@gmail.com>
 * @version    1.0
 * @since      2025-01-05
 */

namespace Core\Base\Models;

/**
 * Base model class.
 */
class Base implements \JsonSerializable {
  use \Core\Base\Traits\Common;
  use \Core\Base\Traits\Errors;
  use \Core\Database\MySQL\Traits\Queries;

  /**
   * Database table
   * @var String
   */
  protected string $table = '';

  /**
   * Singular item label
   * for use in messages / errors
   * @var String
   */
  protected string $itemLabel = '';

  /**
   * Item ID
   * @var Integer|String
   */
  public int|string $id = 0;

  /**
   * Date Created
   * @var Date
   */
  public int $date_created = 0;

  /**
   * User ID of creator
   * @var Integer
   */
  public int $created_by = 0;

  /**
   * Date Modified
   * @var Date
   */
  public int $date_modified = 0;

  /**
   * ID of last user to modify this item
   * @var Integer
   */
  public int $modified_by = 0;

  public Array $tags = [];

  /**
   * Stores an error message to be retrieved by the API
   * @var string
   */
  protected string $_error = '';

  /**
   * Stores a message to be retrieved by the API
   * @var string
   */
  protected string $_message = '';

  /**
   * Construct the model
   *
   * @param string      itemLabel Singular name for this item, eg "User"
   * @param string      $table    Database table where this item is stored, eg "users"
   * @param object|NULL $data     Data to be mapped onto this item
   * @param array       $flags    Additional parameters.
   *
   * @return            $this
   */
  public function __construct(
      string $itemLabel,
      string $table,
      object|NULL $data = NULL,
      array $flags = []
  ) {
    $this->table = $table;
    $this->itemLabel = $itemLabel;
    $this->tags = [];
    $schema = $this->describeTable();
    $this->defaults();
    if ($data) {
      if (isset($data->tags)){
        $this->tags = $data->tags;
      }
      $data = $this->preLoad($data);
      $this->mapData($data);
    }
    $this->postLoad($flags);
    return $this;
  }

  protected function defaults()
  {
    if (isset($_SESSION['user'])) {
      $userId = $this->getCurrentUser()->id;
      $this->created_by = $userId;
      $this->modified_by = $userId;
    }
    $this->date_created = \strtotime('now');
    $this->date_modified = \strtotime('now');
    return $this;
  }

  /**
   * Query the database for an item by a property
   * @param string  $prop The database column to search.
   * @param  mixed    $value IThe value to match
   * 
   * @return object     Database row as an object
   */
  protected function getDataByProperty(string $prop, mixed $value, bool $fail = true)
  {
    if(!$value){
      return null;
    }
    $data = $this->select()
      ->where($prop, '=', $value)
      ->execute(TRUE);
    if (!$data && $fail) {
      $this->error("{$this->itemLabel} with {$prop} = {$value} Not Found", 404, "{$this->itemLabel} Not Found");
    }
    return $data;
  }

  /**
   * Clean data before mapping.
   * @param  object $data [description]
   * @return [type]       [description]
   */
  protected function preLoad($data)
  {
    if (isset($data->tags)){
      $this->tags = $data->tags;
    }
    return $data;

  }

  protected function hasTags()
  {
    return false;
  }

  /**
   * Get additional related data based on provided flags
   * @param  array  $flags [description]
   * @return [type]        [description]
   */
  protected function postLoad(array $flags = [])
  {
    if($this->hasTags() && !in_array('no-tags',$flags)) {
      $this->getTags();
    }
    return $this;
  }

  protected function validate()
  {
    return $this;
  }

  /**
   * Populate this object by using its ID to fetch data
   *
   * @param Integer $id Item's database ID.
   *
   * @return $this
   */
  public function byId(int $id, bool $fail = TRUE, array $flags = [])
  {
    if ($data = $this->byProperty('id', $id, $fail, $flags)) {
      $this->mapData($data);
      $this->postLoad($flags);
      return $this;
    }
  }

  public function byProperty(string $prop, mixed $value, bool $fail = TRUE, array $flags = [])
  {
    if ($data = $this->getDataByProperty($prop, $value, $fail)) {
      $this->mapData($data);
      $this->postLoad($flags);
      return $this;
    } else {
      return false;
    }

  }

  /**
   * Take data from the database, or POST
   * and map it to the model.
   *
   * @param object $data Data to map.
   *
   * @return $this.
   */
  protected function mapData(object $data)
  {
    foreach ($data as $prop => $value) {
      if (property_exists($this, $prop)) {
        $type = trim(gettype($this->$prop));
        switch ($type) {

          case 'boolean':
          case "bool":
            $this->$prop = !!$value;
            break;
          case "integer":
            if (stristr($prop, 'date') && $value) {
              $this->$prop = strtotime($value);
            } else {
              $this->$prop = intval($value);
            }
            break;
          case "double": // (for historical reasons "double" is returned in case of a float, and not simply "float")
            $this->$prop = floatval($value);
            break;
          case "string":
            $this->$prop = strval($value);
            break;
          case "array":
          case "object":
            if (is_string($value) && json_decode($value)) {
              if ($value == '{}' || $value == '"{}"') {
                $this->$prop = new \stdClass();
              } else {
                $this->$prop = json_decode($value);
              }
            } else {
              $this->$prop = $value;
            }
            break;
          case 'NULL':
          case 'null':
          case NULL:
            $this->$prop = NULL;
            break;
          default:
            // echo "$prop => " . json_encode($value) . PHP_EOL;
            // echo ("unhandled type: " . $type);
            $this->$prop = $value;
            break;
        }
      }
    }
    return $this;
  }

  /**
   * Reload this object by getting data from the database
   */
  public function refresh()
  {
    $this->byId($this->id);
    return $this;
  }

  /**
   * Convert the model to an array for use in a query.
   *
   * Apply any other necessary business logic before saving
   *
   * @return Array
   */
  public function prepareSave()
  {
    $currentUser = $this->getCurrentUser();
    if ($currentUser && $currentUser->id > 0){
      $this->modified_by = intval($currentUser->id);
    } else {
      $this->modified_by = 0;
    }

    $data = $this->toArray();
    unset($data['date_created']);
    unset($data['date_modified']);

    unset($data['id']);
    if (isset($data['tags'])) {
      unset($data['tags']);
    }
    foreach ($data as $prop => $value) {
      if (property_exists($this, $prop)) {
        $type = gettype($this->$prop);

        switch ($type) {
          case "bool":
          case "boolean":
            $data[$prop] = $value ? 1 : 0;
            break;
          case "integer":
          case "double":
          case "string":
            if ($value || $value === 0) {
              $data[$prop] = $value;
            } else {
              $data[$prop] = NULL;
            }
            break;
          case "array":
          case "object":
            if (!is_string($value)) {
              $data[$prop] = json_encode($value);
            }
            break;
          case 'NULL':
          case 'NULL':
          case NULL:
            $data[$prop] = NULL;
            break;
          default:
            echo "$prop => $value";
            // die("unhandled type: " . gettype($this->$prop));
            break;
        }
        if (stristr($prop, 'date') && $value > 86400) {
          $data[$prop] = $this->timestamp($value);
        }
      }
    }
    if (!isset($data['created_by']) || !$data['created_by'] ) {
      !$data['created_by'] = 0;
    }
    if (isset($data['table'])){
      unset($data['table']);
    }
    if (isset($data['itemLabel'])){
      unset($data['itemLabel']);
    }
    if (isset($data->_message)){
      unset($data->_message);
    }
    if (isset($data->_error)){
      unset($data->_error);
    }
    return $data;
  }

  /**
   * Save the current model to the database.
   */
  public function save()
  {
    $currentUser = $this->getCurrentUser();
    $data = $this->prepareSave();
    if (!$data) {
      return null;
    }
    if (!isset($this->id) || !$this->id) { // This has no ID, create new
      if ($currentUser) {
        $this->created_by = intval($currentUser->id);
      } else {
        $this->created_by = 0;
      }

      $q = $this->insert($data);
      $this->id = $q->execute();
    } else { // Update existing
      $this->update($data)->where('id', '=', $this->id)->execute();
    }
    return $this->postSave();
  }

  /**
   * Operations to be conducted after the model is saved.
   *
   * By default: refresh this model to acurately reflect the database.
   *
   * @return $this
   */
  protected function postSave()
  {
    $this->saveTags();
    $this->refresh();
    return $this;
  }

  protected function allowDelete()
  {
    return TRUE;
  }

  protected function setError($text)
  {
    $this->_error = $text;
  }

  public function getError(array $replacements = [])
  {
    $error = $this->_error;
    foreach ($replacements as $key => $value) {
      $error = str_replace('{{'.$key.'}}', $value, $error);
    }
    return trim($error);
  }

  protected function setMessage($text)
  {
    $this->_message = $text;
  }
  protected function appendMessage($text)
  {
    $this->_message .= $text;
  }

  public function getMessage(array $replacements = [])
  {
    $message = $this->_message;
    foreach ($replacements as $key => $value) {
      $message = str_replace('{{'.$key.'}}', $value, $message);
    }
    return trim($message);
  }

  /**
   * Delete this item from the database
   *
   * @return bool
   */
  public function delete()
  {
    // Without an ID the delete query would have no WHERE clause and empty the table.
    if (!isset($this->id) || !$this->id || !$this->allowDelete()) {
      return FALSE;
    } else {
      if ($this->deleteQuery($this->table, $this->id)->execute()) {
        return $this->postDelete();
      }
    }
  }

  /**
   * Post-delete cleanup.
   *
   * eg. Delete items from related tables
   *
   * @return $this
   */
  public function postDelete()
  {
    $this->deleteTags();
    return $this;
  }

  public function deleteTags(string $type = '') {
    if (!$this->id) {
      return;
    }
    if (!$type) {
      $type = $this->table;
    }
    $this->deleteQuery('tags_assignments')
      ->where('entity_type', '=', $type)
      ->and('entity_id', '=', $this->id)
      ->execute();
  }

  protected function getAssetURI(string $field, bool $deleteOrphan = FALSE)
  {
    $assetId = $this->$field;
    if ($assetId) {
      $asset = $this->select()->from('assets')->where('id', '=', $assetId)->execute(TRUE);
      if ($asset && $asset->uri && is_file($asset->uri)) {
        return $asset->uri;
      } else if ($deleteOrphan) {
        $this->$field = 0;
        $this->update([$field => 0])->execute();
        $this->$field = 0;
      } else {
        return '';
      }
    }
    return '';
  }

  /**
   * Return JSON to debug
   *
   * @param $label
   */
  protected function debug($label, $data)
  {
    echo $label . PHP_EOL;
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode($data));
  }

  protected function hiddenProps()
  {
    return [
      'table',
      'itemLabel',
      '_error',
      '_message'
    ];
  }

  public function getTags(string $type = '')
  {
    if (!$this->id) {
      return;
    }
    if (!$type) {
      $type = $this->table;
    }
    if (!isset($this->tags)){
      $this->tags = [];
    }
    $tagsQuery = $this->select(['`tags`.*'])->from('tags_assignments')
    ->leftJoin('tags', 'tag_id', 'id')
    ->where('`tags_assignments`.`entity_type`', '=', $type)
    ->and('`tags_assignments`.`entity_id`', '=', $this->id);
    $tags = $tagsQuery->execute(FALSE, '\Core\Tags\Models\Tag');
    $this->tags = array_merge($this->tags, $tags);

    $unique = [];
    $seen = [];

    foreach ($this->tags as $tag) {
        $key = $tag->category . '_' . $tag->id;

        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $unique[] = $tag;
        }
    }

    $this->tags = $unique;
  }

  protected function saveTags()
  {
    // Models that aren't tagged skip this, as getTags() does. A model that
    // unsets $tags (Tag does) would otherwise fail here on every save.
    if (!$this->hasTags() || !isset($this->tags)) {
      return;
    }
    $currentUser = $this->getCurrentUser();
    if ($currentUser){
      $userId = $this->getCurrentUser()->id;
    } else {
      $userId = 0;
    }
    // Get existing tags
    $assignments = $this->select()
      ->from('tags_assignments')
      ->where('entity_type', '=', $this->table)
      ->and('entity_id', '=', $this->id)
      ->execute();

    $oldIDs = array_map(function ($item) {
      return intval($item->tag_id);
    }, $assignments);
    $newIDs = array_map(function ($tag) {
      return intval($tag->id);
    }, $this->tags);
    // get tags to add
    $addIDs = array_diff($newIDs, $oldIDs);
    // get tags to remove
    $delIDs = array_diff($oldIDs, $newIDs);

    // Delete tags
    if ($delIDs) {
      $this->deleteQuery('tags_assignments')
      ->where('entity_type', '=', $this->table)
      ->and('entity_id', '=', $this->id)
      ->and('tag_id', 'IN', $delIDs)
      ->execute();
    }

    // Add new tags
    if ($addIDs) {
      foreach ($addIDs as $tagID) {
        $data = array(
          'entity_type' => $this->table,
          'entity_id' => $this->id,
          'tag_id' => $tagID,
          'created_by' => $userId,
          'modified_by' => $userId
        );
        $this->insert($data, 'tags_assignments')->execute();
      }
    }
  }

  protected function prepareTimestampValue($key, &$data)
  {
    if ($data[$key]) {
      $time = strtotime($data[$key]);
      $data[$key] = $this->timestamp($data[$key]);
    } else {
      unset($data[$key]);
    }
  }

  /**
   * Convert the model to a JSON String.
   */
  public function toString()
  {
    $data = get_object_vars($this);
    foreach ($this->hiddenProps() as $prop) {
      unset($data->$prop);
    }
    $json = json_encode($data);
    return $json;
  }

  /**
   * Convert the model to a simplified object
   */
  public function toObject()
  {
    $json = $this->toString();
    $object = json_decode($json);
    return $object;
  }

  /**
   * Convert the model to a simplified array.
   */
  public function toArray(): array
  {
    // $object = $this->toObject();
    // $array = (array) $object;
    // return $array;
    return $this->jsonSerialize();
  }

  public function jsonSerialize(): mixed
  {
    $data = get_object_vars($this);

    foreach ($this->hiddenProps() as $prop) {
      unset($data[$prop]);
    }

    return $data;
  }
}
