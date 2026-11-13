<?php
/**
 * Default mapper
 *
 * For a public route with a model but no mapper of its own: the model's
 * pageMeta() and schema() if it has them, otherwise its title (or name) and
 * description.
 *
 * PHP version 8.4
 *
 * @category   Helper
 * @package    Tetra
 * @author     Arik Savage <ariksavage@gmail.com>
 * @since      2026-11-13
 */

namespace Core\Pages\Helpers;

class DefaultMapper implements SchemaMapper
{
  public function meta(object $model): PageMeta
  {
    if (method_exists($model, 'pageMeta') && ($meta = $model->pageMeta()) instanceof PageMeta) {
      $meta->jsonLd ??= $this->map($model);
      return $meta;
    }
    $title = strval($model->title ?? $model->name ?? '');
    return new PageMeta(
      title: $title,
      description: PageMeta::plain(strval($model->description ?? '')),
      type: 'article',
      jsonLd: $this->map($model),
    );
  }

  public function map(object $model): ?array
  {
    return method_exists($model, 'schema') ? $model->schema() : NULL;
  }

  public function content(object $model): string
  {
    return '';
  }
}
