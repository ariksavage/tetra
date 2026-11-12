<?php
/**
 * HTML helper
 *
 * For text written in a WYSIWYG field (Tetra's field-wysiwyg): keep its
 * formatting, drop everything else, before it's saved, shown or emailed.
 *
 * PHP version 8.4
 *
 * @category   Helper
 * @package    Tetra
 * @author     Arik Savage <ariksavage@gmail.com>
 * @since      2026-11-11
 */

namespace Core\Base\Helpers;

class Html
{
  /** The tags kept, with the attributes each may keep. */
  const ALLOWED = [
    'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [],
    'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [],
    'h2' => [], 'h3' => [], 'h4' => [],
    'a' => ['href'],
  ];

  /** Removed along with everything inside them. */
  const DROPPED = ['script', 'style', 'iframe', 'object', 'embed', 'template', 'head', 'title', 'meta', 'link', 'svg', 'math', 'form'];

  /**
   * Only the allowed tags and attributes; any other tag is replaced by its
   * contents. Links keep http(s) and mailto addresses only. Plain text (no
   * tags at all) becomes paragraphs.
   */
  public static function clean(string $html): string
  {
    $html = trim(str_replace("\r", '', $html));
    if ($html === '') {
      return '';
    }
    if (!self::isHtml($html)) {
      return self::fromText($html);
    }

    $doc = new \DOMDocument();
    $previous = libxml_use_internal_errors(TRUE);
    $doc->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $body = $doc->getElementsByTagName('body')->item(0);
    if (!$body) {
      return '';
    }
    self::cleanChildren($body);

    $out = '';
    foreach ($body->childNodes as $child) {
      $out .= $doc->saveHTML($child);
    }
    // Empty paragraphs left by the editor.
    $out = preg_replace('#<p>(\s|\x{00A0}|&nbsp;|<br>)*</p>#iu', '', $out);
    return trim($out);
  }

  /** Whether it has any tags, as opposed to plain text. */
  public static function isHtml(string $text): bool
  {
    return (bool) preg_match('#</?[a-z][a-z0-9]*[\s>/]#i', $text);
  }

  /** Plain text as paragraphs: a blank line between them, a line break within. */
  public static function fromText(string $text): string
  {
    $paragraphs = preg_split('/\n\s*\n/', trim(str_replace("\r", '', $text)));
    return implode("\n", array_map(fn($p) => '<p>' . nl2br(htmlspecialchars($p, ENT_QUOTES, 'UTF-8'), FALSE) . '</p>', array_filter($paragraphs, 'strlen')));
  }

  /** As plain text: a blank line between blocks, list items on lines of their own. */
  public static function toText(string $html): string
  {
    $text = preg_replace('#<br\s*/?>\s*#i', "\n", $html);
    $text = preg_replace('#<li[^>]*>#i', "\n- ", $text);
    $text = preg_replace('#</(p|ul|ol|blockquote|h[1-6])>#i', "\n\n", $text);
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace("/[ \t\x{00A0}]+\n/u", "\n", $text);
    // List items one to a line.
    $text = preg_replace("/\n\s*\n- /", "\n- ", $text);
    return trim(preg_replace("/\n{3,}/", "\n\n", $text));
  }

  private static function cleanChildren(\DOMNode $node): void
  {
    // A copy, since the list changes as nodes are replaced.
    foreach (iterator_to_array($node->childNodes) as $child) {
      if ($child instanceof \DOMText) {
        continue;
      }
      if (!$child instanceof \DOMElement) {
        // Comments, processing instructions.
        $node->removeChild($child);
        continue;
      }
      $tag = strtolower($child->tagName);
      if (in_array($tag, self::DROPPED, TRUE)) {
        $node->removeChild($child);
        continue;
      }
      self::cleanChildren($child);
      if (!array_key_exists($tag, self::ALLOWED)) {
        // Keep what's inside; a block of its own (eg. a div) keeps its line.
        while ($child->firstChild) {
          $node->insertBefore($child->firstChild, $child);
        }
        if (in_array($tag, ['div', 'section', 'article', 'tr', 'table', 'h1', 'h5', 'h6', 'pre'], TRUE) && $child->nextSibling) {
          $node->insertBefore($child->ownerDocument->createElement('br'), $child);
        }
        $node->removeChild($child);
        continue;
      }
      foreach (iterator_to_array($child->attributes) as $attribute) {
        if (!in_array($attribute->name, self::ALLOWED[$tag], TRUE)) {
          $child->removeAttribute($attribute->name);
        }
      }
      if ($tag === 'a') {
        $href = trim($child->getAttribute('href'));
        if (!preg_match('#^(https?://|mailto:)#i', $href)) {
          $child->removeAttribute('href');
        } else {
          $child->setAttribute('rel', 'noopener');
        }
      }
    }
  }
}
