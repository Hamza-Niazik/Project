<?php

namespace Drupal\group\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\group\PermissionScopeInterface;

/**
 * Calculates group permissions for an account.
 */
class GroupPermissionChecker implements GroupPermissionCheckerInterface {

  /**
   * The group permission calculator.
   *
   * @var \Drupal\group\Access\GroupPermissionCalculatorInterface
   */
  protected $groupPermissionCalculator;

  /**
   * The group membership loader.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $groupMembershipLoader;

  /**
   * Constructs a GroupPermissionChecker object.
   *
   * @param \Drupal\group\Access\GroupPermissionCalculatorInterface $permission_calculator
   *   The group permission calculator.
   * @param \Drupal\group\GroupMembershipLoaderInterface $group_membership_loader
   *   The group membership loader.
   */
  public function __construct(GroupPermissionCalculatorInterface $permission_calculator, GroupMembershipLoaderInterface $group_membership_loader) {
    $this->groupPermissionCalculator = $permission_calculator;
    $this->groupMembershipLoader = $group_membership_loader;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermissionInGroup($permission, AccountInterface $account, GroupInterface $group) {
    $calculated_permissions = $this->groupPermissionCalculator->calculateFullPermissions($account);

    // First check if anything gave the user individual access to the group.
    $item = $calculated_permissions->getItem(PermissionScopeInterface::INDIVIDUAL_ID, $group->id());
    if ($item && $item->hasPermission($permission)) {
      return TRUE;
    }

    // Then check their synchronized access depending on if they are a member.
    if ($this->groupMembershipLoader->load($group, $account)) {
      $item = $calculated_permissions->getItem(PermissionScopeInterface::INSIDER_ID, $group->bundle());
    }
    else {
      $item = $calculated_permissions->getItem(PermissionScopeInterface::OUTSIDER_ID, $group->bundle());
    }

    return $item && $item->hasPermission($permission);
  }

}
