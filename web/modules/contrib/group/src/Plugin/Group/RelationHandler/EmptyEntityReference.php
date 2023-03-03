<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

/**
 * Provides a default entity reference handler.
 *
 * In case a plugin does not define a handler, the empty class is used so that
 * others can still decorate the plugin-specific service.
 */
class EmptyEntityReference implements EntityReferenceInterface {

  use EntityReferenceTrait;

  /**
   * Constructs a new EmptyEntityReference.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\EntityReferenceInterface $parent
   *   The parent entity reference handler.
   */
  public function __construct(EntityReferenceInterface $parent) {
    $this->parent = $parent;
  }

}
