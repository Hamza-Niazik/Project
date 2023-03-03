<?php

namespace Drupal\group\Plugin\Group\RelationHandlerDefault;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderTrait;

/**
 * Provides group permissions for group relations.
 */
class PermissionProvider implements PermissionProviderInterface {

  use PermissionProviderTrait;

  /**
   * Constructs a new PermissionProvider.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $groupRelationTypeManager
   *   The group relation type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupRelationTypeManagerInterface $groupRelationTypeManager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->groupRelationTypeManager = $groupRelationTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminPermission() {
    return $this->groupRelationType->getAdminPermission();
  }

  /**
   * {@inheritdoc}
   */
  public function getPermission($operation, $target, $scope = 'any') {
    assert(in_array($target, ['relationship', 'entity'], TRUE), '$target must be either "relationship" or "entity"');
    assert(in_array($scope, ['any', 'own'], TRUE), '$scope must be either "any" or "own"');

    if ($target === 'relationship') {
      switch ($operation) {
        case 'view':
          return $this->getRelationshipViewPermission($scope);
        case 'update':
          return $this->getRelationshipUpdatePermission($scope);
        case 'delete':
          return $this->getRelationshipDeletePermission($scope);
        case 'create':
          return $this->getRelationshipCreatePermission();
      }
    }
    elseif ($target === 'entity') {
      switch ($operation) {
        case 'view':
          return $this->getEntityViewPermission($scope);
        case 'view unpublished':
          return $this->getEntityViewUnpublishedPermission($scope);
        case 'update':
          return $this->getEntityUpdatePermission($scope);
        case 'delete':
          return $this->getEntityDeletePermission($scope);
        case 'create':
          return $this->getEntityCreatePermission();
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    $permissions = [];

    // Instead of checking whether this specific permission provider allows for
    // a permission to exist, we check the entire decorator chain. This avoids a
    // lot of copy-pasted code to turn off or rename a permission in a decorator
    // further down the chain.
    $provider_chain = $this->groupRelationTypeManager()->getPermissionProvider($this->pluginId);

    // Provide permissions for the relationship.
    $prefix = 'Relationship:';
    if ($name = $provider_chain->getAdminPermission()) {
      $permissions[$name] = $this->buildPermission("$prefix Administer relations");
      $permissions[$name]['restrict access'] = TRUE;
    }

    if ($name = $provider_chain->getPermission('view', 'relationship')) {
      $permissions[$name] = $this->buildPermission("$prefix View any entity relations");
    }
    if ($name = $provider_chain->getPermission('view', 'relationship', 'own')) {
      $permissions[$name] = $this->buildPermission("$prefix View own entity relations");
    }
    if ($name = $provider_chain->getPermission('update', 'relationship')) {
      $permissions[$name] = $this->buildPermission("$prefix Edit any entity relations");
    }
    if ($name = $provider_chain->getPermission('update', 'relationship', 'own')) {
      $permissions[$name] = $this->buildPermission("$prefix Edit own entity relations");
    }
    if ($name = $provider_chain->getPermission('delete', 'relationship')) {
      $permissions[$name] = $this->buildPermission("$prefix Delete any entity relations");
    }
    if ($name = $provider_chain->getPermission('delete', 'relationship', 'own')) {
      $permissions[$name] = $this->buildPermission("$prefix Delete own entity relations");
    }

    if ($name = $provider_chain->getPermission('create', 'relationship')) {
      $permissions[$name] = $this->buildPermission(
        "$prefix Add entity relations",
        'Allows you to add an existing %entity_type entity to the group.'
      );
    }

    // Provide permissions for the actual entity being added to the group.
    $prefix = 'Entity:';
    if ($name = $provider_chain->getPermission('view', 'entity')) {
      $permissions[$name] = $this->buildPermission("$prefix View any %entity_type entities");
    }
    if ($name = $provider_chain->getPermission('view', 'entity', 'own')) {
      $permissions[$name] = $this->buildPermission("$prefix View own %entity_type entities");
    }
    if ($name = $provider_chain->getPermission('view unpublished', 'entity')) {
      $permissions[$name] = $this->buildPermission("$prefix View any unpublished %entity_type entities");
    }
    if ($name = $provider_chain->getPermission('view unpublished', 'entity', 'own')) {
      $permissions[$name] = $this->buildPermission("$prefix View own unpublished %entity_type entities");
    }
    if ($name = $provider_chain->getPermission('update', 'entity')) {
      $permissions[$name] = $this->buildPermission("$prefix Edit any %entity_type entities");
    }
    if ($name = $provider_chain->getPermission('update', 'entity', 'own')) {
      $permissions[$name] = $this->buildPermission("$prefix Edit own %entity_type entities");
    }
    if ($name = $provider_chain->getPermission('delete', 'entity')) {
      $permissions[$name] = $this->buildPermission("$prefix Delete any %entity_type entities");
    }
    if ($name = $provider_chain->getPermission('delete', 'entity', 'own')) {
      $permissions[$name] = $this->buildPermission("$prefix Delete own %entity_type entities");
    }

    if ($name = $provider_chain->getPermission('create', 'entity')) {
      $permissions[$name] = $this->buildPermission(
        "$prefix Add %entity_type entities",
        'Allows you to create a new %entity_type entity and add it to the group.'
      );
    }

    return $permissions;
  }

  /**
   * Gets the name of the view permission for the relationship.
   *
   * @param string $scope
   *   (optional) Whether the 'any' or 'own' permission name should be returned.
   *   Defaults to 'any'.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  protected function getRelationshipViewPermission($scope = 'any') {
    // @todo Implement view own permission.
    if ($scope === 'any') {
      // Backwards compatible permission name for 'any' scope.
      return "view $this->pluginId relationship";
    }
    return FALSE;
  }

  /**
   * Gets the name of the update permission for the relationship.
   *
   * @param string $scope
   *   (optional) Whether the 'any' or 'own' permission name should be returned.
   *   Defaults to 'any'.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  protected function getRelationshipUpdatePermission($scope = 'any') {
    return "update $scope $this->pluginId relationship";
  }

  /**
   * Gets the name of the delete permission for the relationship.
   *
   * @param string $scope
   *   (optional) Whether the 'any' or 'own' permission name should be returned.
   *   Defaults to 'any'.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  protected function getRelationshipDeletePermission($scope = 'any') {
    return "delete $scope $this->pluginId relationship";
  }

  /**
   * Gets the name of the create permission for the relationship.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  protected function getRelationshipCreatePermission() {
    return "create $this->pluginId relationship";
  }

  /**
   * Gets the name of the view permission for the entity.
   *
   * @param string $scope
   *   (optional) Whether the 'any' or 'own' permission name should be returned.
   *   Defaults to 'any'.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  protected function getEntityViewPermission($scope = 'any') {
    if ($this->definesEntityPermissions) {
      // @todo Implement view own permission.
      if ($scope === 'any') {
        // Backwards compatible permission name for 'any' scope.
        return "view $this->pluginId entity";
      }
    }
    return FALSE;
  }

  /**
   * Gets the name of the view unpublished permission for the entity.
   *
   * @param string $scope
   *   (optional) Whether the 'any' or 'own' permission name should be returned.
   *   Defaults to 'any'.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  protected function getEntityViewUnpublishedPermission($scope = 'any') {
    if ($this->definesEntityPermissions) {
      if ($this->implementsPublishedInterface) {
        // @todo Implement view own unpublished permission and add it here by
        // checking for $this->implementsOwnerInterface.
        if ($scope === 'any') {
          return "view $scope unpublished $this->pluginId entity";
        }
      }
    }
    return FALSE;
  }

  /**
   * Gets the name of the update permission for the entity.
   *
   * @param string $scope
   *   (optional) Whether the 'any' or 'own' permission name should be returned.
   *   Defaults to 'any'.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  protected function getEntityUpdatePermission($scope = 'any') {
    if ($this->definesEntityPermissions) {
      if ($this->implementsOwnerInterface || $scope === 'any') {
        return "update $scope $this->pluginId entity";
      }
    }
    return FALSE;
  }

  /**
   * Gets the name of the delete permission for the entity.
   *
   * @param string $scope
   *   (optional) Whether the 'any' or 'own' permission name should be returned.
   *   Defaults to 'any'.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  protected function getEntityDeletePermission($scope = 'any') {
    if ($this->definesEntityPermissions) {
      if ($this->implementsOwnerInterface || $scope === 'any') {
        return "delete $scope $this->pluginId entity";
      }
    }
    return FALSE;
  }

  /**
   * Gets the name of the create permission for the entity.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  protected function getEntityCreatePermission() {
    if ($this->definesEntityPermissions) {
      return "create $this->pluginId entity";
    }
    return FALSE;
  }

}
