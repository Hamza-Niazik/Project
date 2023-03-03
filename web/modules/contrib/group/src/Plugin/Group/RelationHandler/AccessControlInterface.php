<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides a common interface for group relation access control handlers.
 */
interface AccessControlInterface extends RelationHandlerInterface {

  /**
   * Checks whether an operation is supported for a given target.
   *
   * @param string $operation
   *   The permission operation. Usually "create", "view", "update" or "delete".
   * @param string $target
   *   The target of the operation. Can be 'relationship' or 'entity'.
   *
   * @return bool
   *   Whether the operation is supported.
   */
  public function supportsOperation($operation, $target);

  /**
   * Checks access to an operation on the relationship.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface $group_relationship
   *   The relationship for which to check access.
   * @param string $operation
   *   The operation access should be checked for. Usually one of "view",
   *   "update" or "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   * @param bool $return_as_object
   *   (optional) Defaults to FALSE.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result. Returns a boolean if $return_as_object is FALSE (this
   *   is the default) and otherwise an AccessResultInterface object.
   *   When a boolean is returned, the result of AccessInterface::isAllowed() is
   *   returned, i.e. TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   */
  public function relationshipAccess(GroupRelationshipInterface $group_relationship, $operation, AccountInterface $account, $return_as_object = FALSE);

  /**
   * Checks access to create a relationship.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to check for relationship create access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   * @param bool $return_as_object
   *   (optional) Defaults to FALSE.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result. Returns a boolean if $return_as_object is FALSE (this
   *   is the default) and otherwise an AccessResultInterface object.
   *   When a boolean is returned, the result of AccessInterface::isAllowed() is
   *   returned, i.e. TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   */
  public function relationshipCreateAccess(GroupInterface $group, AccountInterface $account, $return_as_object = FALSE);

  /**
   * Checks access to an operation on the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   The operation access should be checked for. Usually one of "view",
   *   "update" or "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   * @param bool $return_as_object
   *   (optional) Defaults to FALSE.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result. Returns a boolean if $return_as_object is FALSE (this
   *   is the default) and otherwise an AccessResultInterface object.
   *   When a boolean is returned, the result of AccessInterface::isAllowed() is
   *   returned, i.e. TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   */
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account, $return_as_object = FALSE);

  /**
   * Checks access to create an entity.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to check for entity create access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   * @param bool $return_as_object
   *   (optional) Defaults to FALSE.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result. Returns a boolean if $return_as_object is FALSE (this
   *   is the default) and otherwise an AccessResultInterface object.
   *   When a boolean is returned, the result of AccessInterface::isAllowed() is
   *   returned, i.e. TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   */
  public function entityCreateAccess(GroupInterface $group, AccountInterface $account, $return_as_object = FALSE);

}
