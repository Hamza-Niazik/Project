<?php

namespace Drupal\group_recurring_events_series\Plugin;

use Drupal\group\Plugin\GroupContentPermissionProvider;

/**
 * Provides group permissions for events series GroupContent entities.
 */
class GroupEventSeriesPermissionProvider extends GroupContentPermissionProvider {

  /**
   * {@inheritdoc}
   */
  public function getEntityViewUnpublishedPermission($scope = 'any') {
    if ($scope === 'any') {
      // Backwards compatible permission name for 'any' scope.
      return "view unpublished $this->pluginId entity";
    }
    return parent::getEntityViewUnpublishedPermission($scope);
  }

  /**
   * {@inheritdoc}
   *
   * Note:
   * The recurring_events module uses "edit" as an operation while the group
   * module expect "update" to be used. Because of this, we need to translate
   * the value before checking the permission.
   *
   * @todo If recurring_events changes its operation string, this function can be removed.
   */
  public function getPermission($operation, $target, $scope = 'any') {
    if ($operation == 'edit') {
      $operation = 'update';
    }
    return parent::getPermission($operation, $target, $scope);
  }

}
