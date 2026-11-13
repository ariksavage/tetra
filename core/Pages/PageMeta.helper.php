<?php
/**
 * Page metadata
 *
 * What a mapper says about one public page: what the page renderer writes
 * into its head (title, description, canonical URL, Open Graph and Twitter
 * tags) and its JSON-LD. Values are plain text; the renderer escapes them.
 *
 * PHP version 8.4
 *
 * @category   Helper
 * @package    Tetra
 * @author     Arik Savage <ariksavage@gmail.com>
 * @since      2026-11-13
 */

namespace Core\Pages\Helpers;

class PageMeta
{
  /**
   * @param string $title       The page's own title; the renderer adds " | {site name}".
   * @param string $description A sentence or two for search results and link previews.
   * @param string $image       A path from the site's root (eg. /images/...) or a full URL; '' for the site's default.
   * @param string $type        Open Graph type: "article" for a page about one thing, "website" otherwise.
   * @param string $canonical   The page's preferred path (eg. /recipes/mac-n-cheese); '' for the requested path.
   * @param array|null $jsonLd  The page's structured data (eg. a schema.org Recipe), encoded as JSON-LD.
   * @param string $content     Minimal HTML for crawlers that don't run JavaScript, placed in the app's
   *                            root element and replaced when Angular starts; already escaped. '' for none.
   */
  public function __construct(
    public string $title = '',
    public string $description = '',
    public string $image = '',
    public string $type = 'website',
    public string $canonical = '',
    public ?array $jsonLd = NULL,
    public string $content = '',
  ) {}

  /** Plain text for a description: no tags, single spaces, at most $max characters, cut at a word. */
  public static function plain(string $html, int $max = 200): string
  {
    $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    if (mb_strlen($text) <= $max) {
      return $text;
    }
    $cut = mb_substr($text, 0, $max - 1);
    $space = mb_strrpos($cut, ' ');
    return rtrim($space > $max * 0.6 ? mb_substr($cut, 0, $space) : $cut, ' ,.;:') . '…';
  }
}
