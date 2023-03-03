<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

/**
 * Provides a common interface for group relation permission providers.
 */
interface PermissionProviderInterface extends RelationHandlerInterface {

  /**
   * Gets the name of the admin permission.
   *
   * @return string|false
   *   The admin permission name or FALSE if none was set.
   */
  public function getAdminPermission();

  /**
   * Gets the name of the permission for the given operation, target and scope.
   *
   * @param string $operation
   *   The permission operation. Usually "create", "view", "update" or "delete".
   * @param string $target
   *   The target of the operation. Can be 'relationship' or 'entity'.
   * @param string $scope
   *   (optional) Whether the 'any' or 'own' permission name should be returned.
   *   Defaults to 'any'.
   *
   * @return string|false
   *   The permission name or FALSE if the combination is not supported.
   */
  public function getPermission($operation, $target, $scope = 'any');

  /**
   * Provides a list of group permissions the plugin exposes.
   *
   * If you have some group permissions that would only make sense when your
   * plugin is installed, you may define those here. They will not be shown on
   * the permission configuration form unless the plugin is installed.
   *
   * @return array
   *   An array of group permissions, see GroupPermissionHandlerInterface for
   *   the structure of a group permission.
   *
   * @see GroupPermissionHandlerInterface::getPermissions()
   */
  public function buildPermissions();

}
