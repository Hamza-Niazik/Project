<?php

namespace Drupal\flexible_permissions;

use Drupal\Core\Session\AccountInterface;

/**
 * Calculates permissions for an account.
 */
class PermissionChecker implements PermissionCheckerInterface {

  /**
   * The permission calculator.
   *
   * @var \Drupal\flexible_permissions\ChainPermissionCalculatorInterface
   */
  protected $permissionCalculator;

  /**
   * Constructs a PermissionChecker object.
   *
   * @param \Drupal\flexible_permissions\ChainPermissionCalculatorInterface $permission_calculator
   *   The permission calculator.
   */
  public function __construct(ChainPermissionCalculatorInterface $permission_calculator) {
    $this->permissionCalculator = $permission_calculator;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermissionInScope($permission, $scope, $identifier, AccountInterface $account) {
    $calculated_permissions = $this->permissionCalculator->calculatePermissions($account, $scope);
    $item = $calculated_permissions->getItem($scope, $identifier);
    return $item && $item->hasPermission($permission);
  }

}
