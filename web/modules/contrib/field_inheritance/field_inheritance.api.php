<?php

/**
 * @file
 * Custom hooks exposed by the field_inheritance module.
 */

/**
 * Alter the inheritance class used to build the inherited basefield.
 *
 * @var string $class
 *   The class to alter.
 * @var Drupal\Core\Field\FieldDefinitionInterface $field
 *   The field context.
 */
function hook_field_inheritance_inheritance_class_alter(&$class, $field) {
  if ($field->plugin() === 'entity_reference_inheritance') {
    $class = '\Drupal\my_module\EntityReferenceFieldInheritanceFactory';
  }
}

/**
 * Alter the computed value for an inherited field.
 *
 * @param array $value
 *    Array of field item values
 * @param array $context
 *    Array of context information for the field inheritance, with keys:
 *      - source_field
 *      - source_entity
 *      - destination_field
 *      - destination_entity
 *      - method
 */
function field_inheritance_field_inheritance_compute_value_alter(&$value, $context) {
  if ($context['destination_field'] === 'my_field') {
    $value[0]['value'] = 'foo';
  }
}
