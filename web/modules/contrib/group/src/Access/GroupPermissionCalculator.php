<?php

namespace Drupal\group\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\flexible_permissions\CalculatedPermissions;
use Drupal\flexible_permissions\ChainPermissionCalculatorInterface;
use Drupal\flexible_permissions\RefinableCalculatedPermissions;
use Drupal\group\PermissionScopeInterface;

/**
 * Collects group permissions for an account.
 */
class GroupPermissionCalculator implements GroupPermissionCalculatorInterface {

  /**
   * The chain permission calculator.
   *
   * @var \Drupal\flexible_permissions\ChainPermissionCalculatorInterface
   */
  protected $chainCalculator;

  /**
   * Constructs a GroupPermissionCalculator object.
   *
   * @param \Drupal\flexible_permissions\ChainPermissionCalculatorInterface $chain_calculator
   *   The chain permission calculator.
   */
  public function __construct(ChainPermissionCalculatorInterface $chain_calculator) {
    $this->chainCalculator = $chain_calculator;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateFullPermissions(AccountInterface $account) {
    $calculated_permissions = new RefinableCalculatedPermissions();
    $calculated_permissions
      ->merge($this->chainCalculator->calculatePermissions($account, PermissionScopeInterface::OUTSIDER_ID))
      ->merge($this->chainCalculator->calculatePermissions($account, PermissionScopeInterface::INSIDER_ID))
      ->merge($this->chainCalculator->calculatePermissions($account, PermissionScopeInterface::INDIVIDUAL_ID));
    return new CalculatedPermissions($calculated_permissions);
  }

}
