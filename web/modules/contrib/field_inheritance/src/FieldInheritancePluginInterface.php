<?php

namespace Drupal\field_inheritance;

/**
 * FieldInheritancePluginInterface interface definition.
 */
interface FieldInheritancePluginInterface {

  /**
   * Compute the value of the field.
   */
  public function computeValue();

}
