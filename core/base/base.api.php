<?php
/**
 * Basic API
 *
 * Handle general CRUD operations for a particular database table.
 *
 * Will be extended by more specific APIs
 *
 * Or overridden by Application
 *
 * PHP version 8.4
 *
 * @category   API
 * @package    Core
 * @author     Arik Savage <ariksavage@gmail.com>
 * @version    1.0
 * @since      2025-01-10
 */
namespace Core\Base\API;

use \Core\Database\MySQL\Query\Select as selectQuery;


class Base {
  use \Core\Base\Traits\Common;
  use \Core\Base\Traits\Errors;
  use \Core\Database\MySQL\Traits\Queries;

  /**
   * Default table name.
   * @var string
   */
  protected string $table = '';

  /**
   * Cached data sent via POST request.
   * @var object|NULL
   */

  protected object $_postdata;

  /**
   * Namespaced class of the default model
   * @var string
   */
  protected string $model = '';

  /**
   * Label for multiple items
   * @var string
   */
  protected string $plural = 'items';

  /**
   * Label for a single item
   * @var string
   */
  protected string $singular = 'item';

  /**
   * Data object to be returned in JSON response
   * @var object
   * Data
   */
  public object $data;

  /**
   * Text message to be returned in JSON response
   * @var string
   * Message
   */
  public string $message = '';

  /**
   * Permissions dimension (default)
   * @var string
   */
  protected string $dimension = '';

  /**
   * Class constructor
   * @param string $table    Table name. eg "users"
   * @param string $model    Fully namespaced class for returned items. eg "\Core\Users\Models\User"
   * @param string $plural   Plural name for items.                     eg "Users"
   * @param string $singular Singular name for item.                    eg "User"
   */
  public function __construct(
      string $table = '',
      string $model = '',
      string $plural = 'Items',
      string $singular = 'Item'
  ) {
    $this->checkAuth();
    $this->data = new \stdClass();
    $this->message = '';
    $this->table = $table;
    $this->model = $model;
    $this->plural = $plural;
    $this->singular = $singular;
    $this->dimension = $table;

    $this->_postdata = new \stdClass();
  }

  /**
   * Check if authorization is required for this API.
   * May be overridden to allow public access. Not recommended.
   *
   * @return Boolean TRUE if login is required
   */
  public function requiresAuth(): bool
  {
    return TRUE;
  }

  /**
   * If authorization is required, check for an active user
   *
   * @return bool TRUE if authorized
   */
  protected function checkAuth(): bool
  {
    $user = $this->getCurrentUser();
    if ($this->requiresAuth()) {
      if (!$user) {
        $this->error('Login required', 401);
        return FALSE;
      } else {
        $user->updateSession();
        return TRUE;
      }
    } else {
      return TRUE;
    }
  }

  /**
   * Handle a POST request to save an item to the database.
   *
   * @return JSON response
   */
  public function savePOST()
  {
    $this->reqPermission('UPDATE');
    $key = strtolower($this->singular);
    $data = $this->postValue($key);
    $model = new $this->model($data);
    $success = $model->save();
    if ($success) {
      $this->success($this->singular, $model, "Successfully saved {$this->singular}.");
    } else {
      $this->error("Could not save {$this->singular}.", $this->plural, $code = 500, $data);
    }
  }

  /**
   * Handle GET request for a list of items.
   *
   * POST values may include:
   * - int    page    The current page of results to return.
   * - int    per     The number of items per page.
   * - string search  A search term by which to filter results.
   *
   * - Specific APIs may have additional parameters.
   */
  public function listGET($flags = [])
  {

    $this->reqPermission('VIEW');
    $page = $this->getValue('page', 1, 'integer');
    $per = $this->getValue('per', 20, 'integer');
    $query = $this->listQuery($page, $per);
    $list = $this->paginatedResults($query, $page, $per, '', $flags);
    // if (count($list)) {
    $this->success($this->plural, $list);
    // } else {
    //   $this->error("No results found", 404, $this->plural);
    // }
  }

  protected function byId(int $id)
  {
    $model = new $this->model();
    $model->byId($id);
    return $model;
  }

  /**
   * Delete the item with the given $id.
   *
   * @param  int  $id Item's ID
   *
   * @return      JSON success message.
   */
  public function deleteDELETE(int $id)
  {
    $this->reqPermission('DELETE');
    $model = $this->byId($id);
    if ($model->delete()) {
      $this->successMsg("Deleted {$this->singular} $id");
    }
  }

  protected function listGetResults($query, $model = '', $flags = [])
  {
    if (!$model && $this->model) {
      $model = $this->model;
    }
    $results = $query->execute(FALSE, $model, $flags);
    return $results;
  }

  /**
   * Get results from a query, along with pagination information.
   *
   * @param  selectQuery $query    SELECT Query
   * @param  int                        $page     Current page of results to return
   * @param  int                        $perPage  Number of items top return per page
   * @param string $model                         Fully namespaced class for returned items. eg \Core\Users\Models\User
   *
   * @return array<$model>                        Query results
   */
  protected function paginatedResults(
      selectQuery $query,
      int $page,
      int $perPage,
      string $model = '',
      array $flags = []
  ) {
    $total = $query->getTotal();

    $results = $this->listGetResults($query, $model, $flags);
    $pagination = (object) array(
      'current_results' => count($results),
      'total_results' => $total
    );
    $pagination->first_page = 1;
    if ($perPage) {
      $pagination->per_page  = $perPage;
      $lastPage = ceil($total / $perPage);
      $pagination->last_page = $lastPage;
      $pagination->current_page = $page;
      $pagination->next_page = ($page < $lastPage) ? $page + 1 : NULL;
      $pagination->prev_page = ($page > 1) ? $page - 1 : NULL;
    }

    $this->data->pagination = $pagination;
    return $results;
  }

  /**
   * Get an item by its ID
   *
   * @param  int          $id  Item ID
   *
   * @return $this->model      Model
   */
  protected function getById(int $id, $flags = [])
  {
    $model = new $this->model();
    $model->byId($id, $flags);
    return $model;
  }

  /**
   * HTTP GET request for an item by its ID.
   *
   * @param  int           $id  Item ID
   *
   * @return $this->model       Model
   */
  public function idGET(int $id)
  {
    $this->reqPermission('VIEW');
    $model = $this->getById($id);
    $this->success($this->singular, $model);
  }

  /**
   * Get a list of columns to be used for search queries.
   * To be overridden by specific APIs.
   *
   * @return array<string> List of column names.
   */
  protected function searchColumns(): array
  {
    return [];
  }

  protected function searchQuery(&$query, $columns = [])
  {
    $search = $this->getValue('search');
    if ($search) {
      $query->andGroup();
      $searchColumns  = $columns ? $columns : $this->searchColumns();
      if (!count($searchColumns)) {
        $this->error("searchColumns() not configured.");
      }
      foreach ($searchColumns as $k => $column) {
        if ($k > 0) {
          $query->or($column, 'CONTAINS', $search);
        }
      }
      $query->endGroup();
    }
    return $query;
  }

  protected function orderQuery(&$query)
  {
    if ($orderBy = $this->getValue('orderBy', '', 'string')) {
      $orderDir = $this->getValue('orderDir', 'ASC', 'string');
      $query->orderBy($orderBy, $orderDir);
    }
    return $query;
  }

  protected function filterByTags(selectQuery &$query, array $tags, string $type = '', bool $exclude = FALSE)
  {
    if (is_string($tags)) {
      $tags = explode(',', $tags);
    }
    if (count($tags)) {
      if (!$type) {
        $type = $this->table;
      }
      $tagsQ = $this->select(['entity_id'])
      ->from('tag_assignments')
      ->where('entity_type', '=', $type)
      ->andGroup();
      foreach ($tags as $tag) {
        $tagsQ ->or('tag_id', '=', $tag);
      }
      $tagsQ->endGroup();
      $op = $exclude ? 'NOT IN QUERY' : 'IN QUERY';
      $query->and('id', $op, $tagsQ);
    }
  }

  protected function searchQueryFilter(selectQuery &$query): selectQuery
  {
    if ($tags = $this->getValue('tags', [], 'array')) {
      $this->filterByTags($query, $tags);
    }
    return $query;
  }

  /**
   * Build a query to list items.
   * May be expanded by specific APIs
   *
   * @param int $page     Page number of results to return.
   * @param int $per      Number of results per page.
   *
   * @return selectQuery  Query object
   */
  protected function listQuery(int $page = 1, int $per = 20): selectQuery
  {
    $query = $this->select()->from($this->table);
    $this->searchQuery($query);
    $this->searchQueryFilter($query);
    $this->orderQuery($query);
    $query->paginate($page, $per);
    return $query;
  }

  /**
   * Get data from Angular POST request, or from plain $_POST;
   *
   * @return stdClass|NULL POST data
   */
  protected function getPostData(): \stdClass|NULL
  {
    if (!isset($this->_postdata) || $this->_postdata) {
      if ($_POST) {
        $this->_postdata = (object) $_POST;
      } else {
        // Read data from Angular
        if ($rawdata = file_get_contents("php://input")) {
          $this->_postdata = json_decode($rawdata);
        }
      }
    }
    return $this->_postdata;
  }

  /**
   * Get a value from $postdata
   *
   * @param string $key object key to be retrieved
   *
   * @return Mixed Postdata value
   */
  protected function postValue($key, $default = '', $type = 'string')
  {
    $postdata = $this->getPostData();

    $value = $default;
    if (isset($postdata->$key)) {
      $value = $postdata->$key;
    }

    switch ($type) {
      case 'integer':
        $value = intval($value);
        break;
      case 'float':
        $value = floatval($value);
        break;
      case 'boolean':
        $value = !!$value;
        break;
      case 'object':
        if (!$value) {
          $value = new \stdClass();
        }
        break;
      case 'string':
      default:
        break;
    }

    return $value;
  }

    /**
   * Get a value from $the $_GET querystring
   *
   * @param string $key object key to be retrieved
   *
   * @return Mixed Query parameter value
   */
  protected function getValue($key, $default = '', $type = 'string')
  {
    $value = $default;
    if (isset($_GET[$key])) {
      $value = $_GET[$key];
    }
    switch ($type) {
      case 'array':
        if (is_string($value)) {
          $value = explode(',', $value);
        }
        break;
      case 'boolean':
        if ($value !== '' && $value !== NULL) {
          $value = $value == 1;
        }
        break;
      case 'integer':
        $value = intval($value);
        break;
      case 'float':
        $value = floatval($value);
        break;
      case 'boolean':
        $value = !!$value;
        break;
      case 'string':
      default:
        break;
    }

    return $value;
  }

  /**
   * Set a parameter of the API's response data.
   *
   * @param string $key  Data key to be set.
   * @param Mixed  $data Data to be set to the value of $key.
   *
   * @return $this
   */
  public function addData($key = '', $data = NULL)
  {
    if ($key) {
      $key = strtolower($key);
      $this->data->$key = $data;
    }
    return $this;
  }

  /**
   * End the API interaction as successful.
   *
   * @param string $key     Data key to be set.
   * @param mixed  $data    Data to be set to the value of $key.
   * @param string $message User facing success message.
   *
   * @return JSON.
   */
  public function success(string $key = '', mixed $data = NULL, string $message = ''): never
  {
    if ($key) {
      $key = strtolower($key);
      $this->addData($key, $data);
    } else if ($data) {
      $this->data = $data;
    }
    $this->message = trim($message);
    http_response_code(intval(200));
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($this, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
  }

  /**
   * End the API interaction with a success message, and no data.
   *
   * @param string $message User facing success message.
   *
   * @return JSON.
   */
  public function successMsg(string $message = '')
  {
    return $this->success('', NULL, $message);
  }
}
