<?php

namespace Drupal\flexible_permissions;

/**
 * Runs the added calculators one by one until the full permissions are built.
 *
 * Each calculator in the chain can be another chain, which is why this
 * interface extends the permission calculator one.
 *
 * @todo Add alterPermissions($permissions, $account, $scope)?
 */
interface ChainPermissionCalculatorInterface extends PermissionCalculatorInterface {

  /**
   * Adds a calculator.
   *
   * @param \Drupal\flexible_permissions\PermissionCalculatorInterface $calculator
   *   The calculator.
   *
   * @return mixed
   */
  public function addCalculator(PermissionCalculatorInterface $calculator);

  /**
   * Gets all added calculators.
   *
   * @return \Drupal\flexible_permissions\PermissionCalculatorInterface[]
   *   The calculators.
   */
  public function getCalculators();

}
