<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

/**
 * Provides a default group permission provider.
 *
 * In case a plugin does not define a handler, the empty class is used so that
 * others can still decorate the plugin-specific service.
 */
class EmptyPermissionProvider implements PermissionProviderInterface {

  use PermissionProviderTrait;

  /**
   * Constructs a new EmptyPermissionProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface $parent
   *   The parent permission provider.
   */
  public function __construct(PermissionProviderInterface $parent) {
    $this->parent = $parent;
  }

}
