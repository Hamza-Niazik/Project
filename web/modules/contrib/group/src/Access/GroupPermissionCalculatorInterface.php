<?php

namespace Drupal\group\Access;

use Drupal\Core\Session\AccountInterface;

/**
 * Aggregates the calculated permissions from all scopes into one set.
 */
interface GroupPermissionCalculatorInterface {

  /**
   * Calculates the full group permissions for an account.
   *
   * This includes all scopes: outsider, insider, individual.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to retrieve the permissions.
   *
   * @return \Drupal\flexible_permissions\CalculatedPermissionsInterface
   *   An object representing the full group permissions.
   */
  public function calculateFullPermissions(AccountInterface $account);

}
