<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

/**
 * Provides a default post install handler.
 *
 * In case a plugin does not define a handler, the empty class is used so that
 * others can still decorate the plugin-specific service.
 */
class EmptyPostInstall implements PostInstallInterface {

  use PostInstallTrait;

  /**
   * Constructs a new EmptyPostInstall.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PostInstallInterface $parent
   *   The parent post install handler.
   */
  public function __construct(PostInstallInterface $parent) {
    $this->parent = $parent;
  }

}
