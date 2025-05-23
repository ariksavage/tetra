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
class Base {
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
    $schema = $this->describeTable();
    $this->defaults();
    if ($data) {
      if (isset($data->id)) {
        $this->byId($data->id);
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
   * Query the database for an item by its ID
   * @param  int    $id Item ID
   * @return object     Database row as an object
   */
  protected function getDataById(int $id)
  {
    $data = $this->select()
      ->where('id', '=', $id)
      ->execute(TRUE);
    if (!$data) {
      $this->error("{$this->itemLabel} {$id} Not Found", 404, "{$this->itemLabel} Not Found");
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
    return $data;
  }

  /**
   * Get additional related data based on provided flags
   * @param  array  $flags [description]
   * @return [type]        [description]
   */
  protected function postLoad(array $flags = [])
  {
    if (in_array('WITH_TAGS', $flags)) {
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
  public function byId(int $id, array $flags = [])
  {
    if ($data = $this->getDataById($id)) {
      $this->mapData($data);
      $this->postLoad($flags);
      return $this;
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
        $type = gettype($this->$prop);
        switch ($type) {
          case "boolean":
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
            echo "$prop => $value";
            die("unhandled type: " . gettype($this->$prop));
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
    $this->modified_by = intval($currentUser->id);

    $data = $this->toArray();
    unset($data['date_created']);
    unset($data['date_modified']);

    unset($data['id']);
    if (isset($data['tags'])) {
      $this->saveTags();
      unset($data['tags']);
    }
    foreach ($data as $prop => $value) {
      if (property_exists($this, $prop)) {
        $type = gettype($this->$prop);

        switch ($type) {
          case "boolean":
            $data[$prop] = $value ? 1 : 0;
            break;
          case "integer":
          case "double":
          case "string":
            if ($value) {
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
            die("unhandled type: " . gettype($this->$prop));
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
    return $data;
  }

  /**
   * Save the current model to the database.
   */
  public function save()
  {
    $currentUser = $this->getCurrentUser();
    $this->modified_by = intval($currentUser->id);
    $data = $this->prepareSave();
    if (!isset($this->id) || !$this->id) { // This has no ID, create new
      $this->created_by = intval($currentUser->id);

      $this->id = $this->insert($data)->execute();
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
   * @return Boolean
   */
  public function delete()
  {
    if (!$this->allowDelete()) {
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
    return $this;
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
    return array();
  }

  public function getTags($type = NULL)
  {
    if (!$type) {
      $type = $this->table;
    }
    $tagsQuery = $this->select(['`tags`.*'])->from('tag_assignments')
    ->leftJoin('tags', 'tag_id', 'id')
    ->where('`tag_assignments`.`entity_type`', '=', $type)
    ->and('`tag_assignments`.`entity_id`', '=', $this->id);
    $this->tags = $tagsQuery->execute(FALSE, '\Core\Tags\Models\Tag');
    foreach ($this->tags as &$tag) {
      $tag->id = intval($tag->id);
      $tag->tenant_id = intval($tag->tenant_id);
    }
  }

  protected function saveTags()
  {
    $currentUser = $this->getCurrentUser();
    if (!$currentUser) {
      die('Could not find current user');
    }
    $userId = $this->getCurrentUser()->id;
    // Get existing tags
    $assignments = $this->select()
      ->from('tag_assignments')
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
      $this->deleteQuery('tag_assignments')
      ->where('tag_id', 'IN', $delIDs)
      ->execute();
    }

    // Add new tags
    if ($addIDs) {
      foreach ($addIDs as $tagID) {
        $data = array(
          'entity_type' => $this->table,
          'entity_id' => $this->id,
          'tenant_id' => $this->tenant_id,
          'tag_id' => $tagID,
          'created_by' => $userId,
          'modified_by' => $userId
        );
        $this->insert($data, 'tag_assignments')->execute();
      }
    }

    unset($this->tags);
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
    $data = $this;
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
  public function toArray()
  {
    $object = $this->toObject();
    $array = (array) $object;
    return $array;
  }
}
