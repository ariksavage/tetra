<?php
/**
 * Courses API
 *
 * Manage courses
 *
 * Handle entity tags
 *
 * PHP version 8.4
 *
 * @category   API
 * @package    Core
 * @author     Eric Savage <eric@hiapti.com>
 * @version    1.0
 * @since      2025-04-01
 */
namespace Core\Tags\API;

use \Core\Tags\Models\Tag;
/**
 * Class Tags
 *
 * Handles all tags-related operations
 */
class Tags extends \Core\Base\API\Base {

  /**
   * Class constructor
   *
   * Initializes the Courses API, requires 'courses' permission, and loads the required model.
   */
  public function __construct()
  {
    $this->requirePermission('tags');
    parent::__construct('tags', '\Core\Tags\Models\Tag', 'Tags', 'Tag');
  }

  public function newPOST()
  {
    $label = $this->postValue('label', '', 'string');
    $tag = new Tag();
    $tag->label = $label;
    $tag->save();
    $this->success('tag', $tag, "Tag \"$label\" created");
  }

  public function typeGET(string $type)
  {
    $tags = $this->select(['`tags`.*'])
      ->from('tag_assignments')
      ->leftJoin('tags', 'tag_id', 'id')
      ->where('entity_type', '=', $type)
      ->groupBy('`tag_assignments`.`tag_id`')
      ->orderBy('`tags`.`label`')
      ->execute(FALSE, '\Core\Tags\Models\Tag');
      $this->success('tags', $tags);
  }
}
