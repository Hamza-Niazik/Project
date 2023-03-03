<?php

namespace Drupal\group_test_plugin_alter\Plugin\Group\RelationHandler;

use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderTrait;

/**
 * Alters admin permission for all plugins to original + 'foo'.
 */
class FooAdminPermissionProvider implements PermissionProviderInterface {

  use PermissionProviderTrait;

  /**
   * Constructs a new FooAdminPermissionProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface $parent
   *   The parent permission provider.
   */
  public function __construct(PermissionProviderInterface $parent) {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminPermission() {
    return $this->parent->getAdminPermission() . 'foo';
  }

}
