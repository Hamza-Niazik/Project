<?php

namespace Drupal\gnode\Plugin\Group\RelationHandler;

use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderTrait;
use Drupal\group\Plugin\Group\RelationHandler\RelationHandlerTrait;

/**
 * Provides group permissions for the group_node relation plugin.
 */
class GroupNodePermissionProvider implements PermissionProviderInterface {

  use PermissionProviderTrait;

  /**
   * Constructs a new GroupMembershipPermissionProvider.
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
  public function getPermission($operation, $target, $scope = 'any') {
    // Backwards compatible permission name for 'any' scope.
    if ($operation === 'view unpublished' && $target === 'entity' && $scope === 'any') {
      return "$operation $this->pluginId $target";
    }
    return $this->parent->getPermission($operation, $target, $scope);
  }

}
