<?php

namespace Drupal\group\Entity\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupRelationshipTypeInterface;

/**
 * Defines the access control handler for the relationship type entity type.
 *
 * @see \Drupal\group\Entity\GroupRelationshipType
 */
class GroupRelationshipTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    assert($entity instanceof GroupRelationshipTypeInterface);
    if ($operation == 'delete') {
      return parent::checkAccess($entity, $operation, $account)->addCacheableDependency($entity);
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
