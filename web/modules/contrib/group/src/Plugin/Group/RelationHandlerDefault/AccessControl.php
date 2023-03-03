<?php

namespace Drupal\group\Plugin\Group\RelationHandlerDefault;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlTrait;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;

/**
 * Provides access control for group relations.
 */
class AccessControl implements AccessControlInterface {

  use AccessControlTrait;

  /**
   * Constructs a new AccessControl.
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
  public function supportsOperation($operation, $target) {
    assert(in_array($target, ['relationship', 'entity'], TRUE), '$target must be either "relationship" or "entity"');
    $permissions = [$this->permissionProvider->getPermission($operation, $target, 'any')];

    // We know relations have owners, but need to check for the target entity.
    if ($target === 'relationship' || $this->implementsOwnerInterface) {
      $permissions[] = $this->permissionProvider->getPermission($operation, $target, 'own');
    }

    foreach ($permissions as $permission) {
      if ($permission !== FALSE) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Checks operation support across the entire decorator chain.
   *
   * Instead of checking whether this specific access control handler supports
   * the operation, we check the entire decorator chain. This avoids a lot of
   * copy-pasted code to manually support an operation in a decorator further
   * down the chain.
   *
   * @param string $operation
   *   The permission operation. Usually "create", "view", "update" or "delete".
   * @param string $target
   *   The target of the operation. Can be 'relationship' or 'entity'.
   *
   * @return bool
   *   Whether the operation is supported.
   */
  protected function chainSupportsOperation($operation, $target) {
    $access_control_chain = $this->groupRelationTypeManager()->getAccessControlHandler($this->pluginId);
    return $access_control_chain->supportsOperation($operation, $target);
  }

  /**
   * {@inheritdoc}
   */
  public function relationshipAccess(GroupRelationshipInterface $group_relationship, $operation, AccountInterface $account, $return_as_object = FALSE) {
    if (!$this->chainSupportsOperation($operation, 'relationship')) {
      return $return_as_object ? AccessResult::neutral() : FALSE;
    }

    // Check if the account is the owner.
    $is_owner = $group_relationship->getOwnerId() === $account->id();

    // Add in the admin permission and filter out the unsupported permissions.
    $permissions = [
      $this->permissionProvider->getAdminPermission(),
      $this->permissionProvider->getPermission($operation, 'relationship', 'any'),
    ];
    $own_permission = $this->permissionProvider->getPermission($operation, 'relationship', 'own');
    if ($is_owner) {
      $permissions[] = $own_permission;
    }
    $permissions = array_filter($permissions);

    // If we still have permissions left, check for access.
    $result = AccessResult::neutral();
    if (!empty($permissions)) {
      $result = GroupAccessResult::allowedIfHasGroupPermissions($group_relationship->getGroup(), $account, $permissions, 'OR');
    }

    // If there was an owner permission to check, the result needs to vary per
    // user. We also need to add the relation as a dependency because if its
    // owner changes, someone might suddenly gain or lose access.
    if ($own_permission) {
      // @todo Not necessary if admin, could boost performance here.
      $result->cachePerUser()->addCacheableDependency($group_relationship);
    }

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function relationshipCreateAccess(GroupInterface $group, AccountInterface $account, $return_as_object = FALSE) {
    if (!$this->chainSupportsOperation('create', 'relationship')) {
      return $return_as_object ? AccessResult::neutral() : FALSE;
    }
    $permission = $this->permissionProvider->getPermission('create', 'relationship');
    return $this->combinedPermissionCheck($group, $account, $permission, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account, $return_as_object = FALSE) {
    // We only check unpublished vs published for "view" right now. If we ever
    // start supporting other operations, we need to remove the "view" check.
    $check_published = $operation === 'view' && $this->implementsPublishedInterface;

    // Figure out which operation to check.
    $operation_to_check = $operation;
    if ($check_published && !$entity->isPublished()) {
      $operation_to_check = "$operation unpublished";
    }

    // The Group module's ideology is that if you want to do something to a
    // grouped entity, you need Group to explicitly allow access or else the
    // result will be forbidden. Having said that, if we do not support an
    // operation yet, it's probably nicer to return neutral here. This way, any
    // module that exposes new operations will work as intended AND NOT HAVE
    // GROUP ACCESS CHECKS until Group specifically implements said operations.
    if (!$this->chainSupportsOperation($operation_to_check, 'entity')) {
      return $return_as_object ? AccessResult::neutral() : FALSE;
    }

    $group_relationships = $this->entityTypeManager()
      ->getStorage('group_relationship')
      ->loadByEntity($entity, $this->pluginId);

    // If this plugin is not being used by the entity, we have nothing to say.
    if (empty($group_relationships)) {
      return $return_as_object ? AccessResult::neutral() : FALSE;
    }

    // Check if the account is the owner and an owner permission is supported.
    $is_owner = FALSE;
    if ($this->implementsOwnerInterface) {
      $is_owner = $entity->getOwnerId() === $account->id();
    }

    // Add in the admin permission and filter out the unsupported permissions.
    $permissions = [
      $this->permissionProvider->getAdminPermission(),
      $this->permissionProvider->getPermission($operation_to_check, 'entity', 'any'),
    ];
    $own_permission = $this->permissionProvider->getPermission($operation_to_check, 'entity', 'own');
    if ($is_owner) {
      $permissions[] = $own_permission;
    }
    $permissions = array_filter($permissions);

    // If we still have permissions left, check for access.
    $result = AccessResult::neutral();
    if (!empty($permissions)) {
      foreach ($group_relationships as $group_relationship) {
        $result = GroupAccessResult::allowedIfHasGroupPermissions($group_relationship->getGroup(), $account, $permissions, 'OR');
        if ($result->isAllowed()) {
          break;
        }
      }
    }

    // If we did not allow access, we need to explicitly forbid access to avoid
    // other modules from granting access where Group promised the entity would
    // be inaccessible.
    if (!$result->isAllowed()) {
      $result = AccessResult::forbidden()->addCacheableDependency($result);
    }

    // If there was an owner permission to check, the result needs to vary per
    // user. We also need to add the entity as a dependency because if its owner
    // changes, someone might suddenly gain or lose access.
    if (!empty($own_permission)) {
      // @todo Not necessary if admin, could boost performance here.
      $result->cachePerUser();
    }

    // If we needed to check for the owner permission or published access, we
    // need to add the entity as a dependency because the owner or publication
    // status might change.
    if (!empty($own_permission) || $check_published) {
      // @todo Not necessary if admin, could boost performance here.
      $result->addCacheableDependency($entity);
    }

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function entityCreateAccess(GroupInterface $group, AccountInterface $account, $return_as_object = FALSE) {
    if (!$this->chainSupportsOperation('create', 'entity')) {
      return $return_as_object ? AccessResult::neutral() : FALSE;
    }
    $permission = $this->permissionProvider->getPermission('create', 'entity');
    return $this->combinedPermissionCheck($group, $account, $permission, $return_as_object);
  }

}
