<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Trait for group relation permission providers.
 *
 * This trait takes care of common logic for permission providers. Please make
 * sure your handler service asks for the entity_type.manager service and sets
 * to the $this->entityTypeManager property in its constructor.
 */
trait AccessControlTrait {

  use RelationHandlerTrait {
    init as traitInit;
  }

  /**
   * The entity type the plugin handler is for.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * Whether the target entity type implements the EntityOwnerInterface.
   *
   * @var bool
   */
  protected $implementsOwnerInterface;

  /**
   * Whether the target entity type implements the EntityPublishedInterface.
   *
   * @var bool
   */
  protected $implementsPublishedInterface;

  /**
   * The plugin's permission provider.
   *
   * @var \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface
   */
  protected $permissionProvider;

  /**
   * {@inheritdoc}
   */
  public function init($plugin_id, GroupRelationTypeInterface $group_relation_type) {
    $this->traitInit($plugin_id, $group_relation_type);
    $this->entityType = $this->entityTypeManager()->getDefinition($group_relation_type->getEntityTypeId());
    $this->implementsOwnerInterface = $this->entityType->entityClassImplements(EntityOwnerInterface::class);
    $this->implementsPublishedInterface = $this->entityType->entityClassImplements(EntityPublishedInterface::class);
    $this->permissionProvider = $this->groupRelationTypeManager()->getPermissionProvider($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsOperation($operation, $target) {
    if (!isset($this->parent)) {
      throw new \LogicException('Using AccessControlTrait without assigning a parent or overwriting the methods.');
    }
    return $this->parent->supportsOperation($operation, $target);
  }

  /**
   * {@inheritdoc}
   */
  public function relationshipAccess(GroupRelationshipInterface $group_relationship, $operation, AccountInterface $account, $return_as_object = FALSE) {
    if (!isset($this->parent)) {
      throw new \LogicException('Using AccessControlTrait without assigning a parent or overwriting the methods.');
    }
    return $this->parent->relationshipAccess($group_relationship, $operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function relationshipCreateAccess(GroupInterface $group, AccountInterface $account, $return_as_object = FALSE) {
    if (!isset($this->parent)) {
      throw new \LogicException('Using AccessControlTrait without assigning a parent or overwriting the methods.');
    }
    return $this->parent->relationshipCreateAccess($group, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account, $return_as_object = FALSE) {
    if (!isset($this->parent)) {
      throw new \LogicException('Using AccessControlTrait without assigning a parent or overwriting the methods.');
    }
    return $this->parent->entityAccess($entity, $operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function entityCreateAccess(GroupInterface $group, AccountInterface $account, $return_as_object = FALSE) {
    if (!isset($this->parent)) {
      throw new \LogicException('Using AccessControlTrait without assigning a parent or overwriting the methods.');
    }
    return $this->parent->entityCreateAccess($group, $account, $return_as_object);
  }

  /**
   * Checks the provided permission alongside the admin permission.
   *
   * Important: Only one permission needs to match.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to check for access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   * @param string $permission
   *   The names of the permission to check for.
   * @param bool $return_as_object
   *   Whether to return the result as an object or boolean.
   *
   * @return bool|\Drupal\Core\Access\AccessResult
   *   The access result. Returns a boolean if $return_as_object is FALSE (this
   *   is the default) and otherwise an AccessResultInterface object.
   *   When a boolean is returned, the result of AccessInterface::isAllowed() is
   *   returned, i.e. TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   */
  protected function combinedPermissionCheck(GroupInterface $group, AccountInterface $account, $permission, $return_as_object) {
    $result = AccessResult::neutral();

    // Add in the admin permission and filter out the unsupported permissions.
    $permissions = [$permission, $this->permissionProvider->getAdminPermission()];
    $permissions = array_filter($permissions);

    // If we still have permissions left, check for access.
    if (!empty($permissions)) {
      $result = GroupAccessResult::allowedIfHasGroupPermissions($group, $account, $permissions, 'OR');
    }

    return $return_as_object ? $result : $result->isAllowed();
  }

}
