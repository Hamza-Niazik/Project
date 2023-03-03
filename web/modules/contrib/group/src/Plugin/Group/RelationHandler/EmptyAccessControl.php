<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

/**
 * Provides a default access control handler.
 *
 * In case a plugin does not define a handler, the empty class is used so that
 * others can still decorate the plugin-specific service.
 */
class EmptyAccessControl implements AccessControlInterface {

  use AccessControlTrait;

  /**
   * Constructs a new EmptyAccessControl.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface $parent
   *   The parent access control handler.
   */
  public function __construct(AccessControlInterface $parent) {
    $this->parent = $parent;
  }

}
