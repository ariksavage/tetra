<?php
/**
 * Schema mapper
 *
 * Turns one loaded model into what crawlers get for its public page. The
 * route map names a model's mapper ("schema": "Recipes\\Schema\\RecipeSchema",
 * which the class loader finds at core/Recipes/RecipeSchema.schema.php);
 * without one, DefaultMapper uses the model's own pageMeta() and schema(),
 * or its title and description.
 *
 * PHP version 8.4
 *
 * @category   Helper
 * @package    Tetra
 * @author     Arik Savage <ariksavage@gmail.com>
 * @since      2026-11-13
 */

namespace Core\Pages\Helpers;

interface SchemaMapper
{
  /** The head: title, description, image, Open Graph type, canonical path, JSON-LD. */
  public function meta(object $model): PageMeta;

  /**
   * The page's structured data, eg. a schema.org Recipe. Any shape the app
   * wants (schema.org or not), as long as it encodes as JSON; NULL for none.
   */
  public function map(object $model): ?array;

  /**
   * A minimal, plain HTML version of the page for crawlers that don't run
   * JavaScript: placed inside the app's root element, replaced when Angular
   * starts. '' for none.
   */
  public function content(object $model): string;
}
