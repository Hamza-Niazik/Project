<?php

namespace Drupal\field_inheritance\Plugin\FieldInheritance;

use Drupal\field_inheritance\FieldInheritancePluginInterface;

/**
 * Entity Reference Inheritance plugin.
 *
 * @FieldInheritance(
 *   id = "entity_reference_inheritance",
 *   name = @Translation("Entity Reference Field Inheritance"),
 *   types = {
 *     "entity_reference",
 *     "image",
 *     "file",
 *     "webform",
 *     "entity_reference_revisions",
 *     "paragraphs"
 *   }
 * )
 */
class EntityReferenceFieldInheritancePlugin extends FieldInheritancePluginBase implements FieldInheritancePluginInterface {
}
