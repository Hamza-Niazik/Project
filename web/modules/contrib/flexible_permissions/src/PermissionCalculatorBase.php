<?php

namespace Drupal\flexible_permissions;

use Drupal\Core\Session\AccountInterface;

/**
 * Base class for permission calculators.
 */
abstract class PermissionCalculatorBase implements PermissionCalculatorInterface {

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, $scope) {
    return (new RefinableCalculatedPermissions())->addCacheContexts($this->getPersistentCacheContexts($scope));
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts($scope) {
    return [];
  }

}
