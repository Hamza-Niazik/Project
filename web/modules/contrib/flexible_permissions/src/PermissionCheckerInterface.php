<?php

namespace Drupal\flexible_permissions;

use Drupal\Core\Session\AccountInterface;

/**
 * Defines the permission checker interface.
 */
interface PermissionCheckerInterface {

  /**
   * Checks whether an account has a permission in a scope.
   *
   * @param string $permission
   *   The name of the permission to check for.
   * @param string $scope
   *   The name of the scope to check in.
   * @param string|int $identifier
   *   The identifier in the provided scope.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check the permissions.
   *
   * @return bool
   *   Whether the account has the permission.
   */
  public function hasPermissionInScope($permission, $scope, $identifier, AccountInterface $account);

}
