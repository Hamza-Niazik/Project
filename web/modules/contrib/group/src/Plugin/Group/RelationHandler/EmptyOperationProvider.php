<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

/**
 * Provides a default operation provider.
 *
 * In case a plugin does not define a handler, the empty class is used so that
 * others can still decorate the plugin-specific service.
 */
class EmptyOperationProvider implements OperationProviderInterface {

  use OperationProviderTrait;

  /**
   * Constructs a new EmptyOperationProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\OperationProviderInterface $parent
   *   The parent operation provider.
   */
  public function __construct(OperationProviderInterface $parent) {
    $this->parent = $parent;
  }

}
