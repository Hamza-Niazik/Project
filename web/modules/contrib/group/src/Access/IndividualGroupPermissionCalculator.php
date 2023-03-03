<?php

namespace Drupal\group\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flexible_permissions\CalculatedPermissionsItem;
use Drupal\flexible_permissions\PermissionCalculatorBase;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\group\PermissionScopeInterface;

/**
 * Calculates individual group permissions for an account.
 */
class IndividualGroupPermissionCalculator extends PermissionCalculatorBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The membership loader service.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $membershipLoader;

  /**
   * Constructs a IndividualGroupPermissionCalculator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\GroupMembershipLoaderInterface $membership_loader
   *   The group membership loader service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupMembershipLoaderInterface $membership_loader) {
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipLoader = $membership_loader;
  }

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, $scope) {
    $calculated_permissions = parent::calculatePermissions($account, $scope);

    if ($scope !== PermissionScopeInterface::INDIVIDUAL_ID) {
      return $calculated_permissions;
    }

    // The member permissions need to be recalculated whenever the user is added
    // to or removed from a group.
    $calculated_permissions->addCacheTags(['group_relationship_list:plugin:group_membership:entity:' . $account->id()]);

    foreach ($this->membershipLoader->loadByUser($account) as $group_membership) {
      // If the member's roles change, so do the permissions.
      $calculated_permissions->addCacheableDependency($group_membership);

      foreach ($group_membership->getRoles(FALSE) as $group_role) {
        $item = new CalculatedPermissionsItem(
          $group_role->getScope(),
          $group_membership->getGroup()->id(),
          $group_role->getPermissions(),
          $group_role->isAdmin()
        );
        $calculated_permissions->addCacheableDependency($group_role);
        $calculated_permissions->addItem($item);
      }
    }

    return $calculated_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts($scope) {
    if ($scope === PermissionScopeInterface::INDIVIDUAL_ID) {
      return ['user'];
    }
    return [];
  }

}
