<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Trait for group relation entity reference handlers.
 */
trait EntityReferenceTrait {

  use RelationHandlerTrait;

  /**
   * {@inheritdoc}
   */
  public function configureField(BaseFieldDefinition $entity_reference) {
    if (!isset($this->parent)) {
      throw new \LogicException('Using EntityReferenceTrait without assigning a parent or overwriting the methods.');
    }
    return $this->parent->configureField($entity_reference);
  }

}
