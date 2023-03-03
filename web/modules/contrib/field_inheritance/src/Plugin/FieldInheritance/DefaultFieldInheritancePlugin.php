<?php

namespace Drupal\field_inheritance\Plugin\FieldInheritance;

use Drupal\field_inheritance\FieldInheritancePluginInterface;

/**
 * Default Inheritance plugin.
 *
 * @FieldInheritance(
 *   id = "default_inheritance",
 *   name = @Translation("Default Field Inheritance"),
 *   types = {
 *     "any",
 *   }
 * )
 */
class DefaultFieldInheritancePlugin extends FieldInheritancePluginBase implements FieldInheritancePluginInterface {
}
