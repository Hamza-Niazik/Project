<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

/**
 * Provides group permissions for the group_membership relation plugin.
 */
class GroupMembershipPermissionProvider implements PermissionProviderInterface {

  use PermissionProviderTrait;

  /**
   * Constructs a new GroupMembershipPermissionProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface $parent
   *   The default permission provider.
   */
  public function __construct(PermissionProviderInterface $parent) {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermission($operation, $target, $scope = 'any') {
    // The following permissions are handled by the admin permission or have a
    // different permission name.
    if ($target === 'relationship') {
      switch ($operation) {
        case 'create':
          return FALSE;

        case 'delete':
          return $scope === 'own' ? 'leave group' : FALSE;

        case 'update':
          if ($scope === 'any') {
            return FALSE;
          }
          break;
      }
    }
    return $this->parent->getPermission($operation, $target, $scope);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    $permissions = $this->parent->buildPermissions();

    // Add in the join group permission.
    $permissions['join group'] = [
      'title' => 'Join group',
      'allowed for' => ['outsider'],
    ];

    if ($name = $this->getPermission('update', 'relationship', 'own')) {
      $permissions[$name]['title'] = 'Edit own membership';
      $permissions[$name]['allowed for'] = ['member'];
    }

    // We know this exists, but check either way just to be safe.
    if ($name = $this->getPermission('delete', 'relationship', 'own')) {
      $permissions[$name]['title'] = 'Leave group';
      $permissions[$name]['allowed for'] = ['member'];
    }

    if ($name = $this->getAdminPermission()) {
      $permissions[$name]['title'] = 'Administer group members';
    }

    if ($name = $this->getPermission('view', 'relationship')) {
      $permissions[$name]['title'] = 'View individual group members';
    }

    return $permissions;
  }

}
