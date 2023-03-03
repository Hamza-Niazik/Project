<?php

namespace Drupal\group\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flexible_permissions\CalculatedPermissionsItem;
use Drupal\flexible_permissions\PermissionCalculatorBase;
use Drupal\group\Entity\GroupRoleInterface;
use Drupal\group\PermissionScopeInterface;

/**
 * Calculates synchronized group permissions for an account.
 */
class SynchronizedGroupPermissionCalculator extends PermissionCalculatorBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SynchronizedGroupPermissionCalculator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, $scope) {
    $calculated_permissions = parent::calculatePermissions($account, $scope);

    if ($scope !== PermissionScopeInterface::OUTSIDER_ID && $scope !== PermissionScopeInterface::INSIDER_ID) {
      return $calculated_permissions;
    }

    // @todo Introduce config:group_role_list:scope:SCOPE cache tag.
    // If a new group role is introduced, we need to recalculate the permissions
    // for the provided scope.
    $calculated_permissions->addCacheTags(['config:group_role_list']);

    $roles = $account->getRoles();
    $group_roles = $this->entityTypeManager->getStorage('group_role')->loadByProperties([
      'scope' => $scope,
      'global_role' => $roles,
    ]);

    foreach ($group_roles as $group_role) {
      assert($group_role instanceof GroupRoleInterface);
      $item = new CalculatedPermissionsItem(
        $group_role->getScope(),
        $group_role->getGroupTypeId(),
        $group_role->getPermissions(),
        $group_role->isAdmin()
      );
      $calculated_permissions->addItem($item);
      $calculated_permissions->addCacheableDependency($group_role);
    }

    return $calculated_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts($scope) {
    if ($scope === PermissionScopeInterface::OUTSIDER_ID || $scope === PermissionScopeInterface::INSIDER_ID) {
      return ['user.roles'];
    }
    return [];
  }

}
