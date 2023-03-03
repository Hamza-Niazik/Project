<?php

namespace Drupal\recurring_events;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the event instance entity.
 *
 * @see \Drupal\recurring_events\Entity\EventInstance
 */
class EventInstanceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess is called with the
   * $operation as defined in the routing.yml file.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        $status = $entity->isPublished();
        if (!$status) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished eventinstance entity');
        }
        return AccessResult::allowedIfHasPermission($account, 'view eventinstance entity');

      case 'edit':
        if ($account->id() !== $entity->getOwnerId()) {
          return AccessResult::allowedIfHasPermission($account, 'edit eventinstance entity');
        }
        return AccessResult::allowedIfHasPermissions($account, [
          'edit eventinstance entity',
          'edit own eventinstance entity',
        ], 'OR');

      case 'delete':
        if ($account->id() !== $entity->getOwnerId()) {
          return AccessResult::allowedIfHasPermission($account, 'delete eventinstance entity');
        }
        return AccessResult::allowedIfHasPermissions($account, [
          'delete eventinstance entity',
          'delete own eventinstance entity',
        ], 'OR');

      case 'clone':
        return AccessResult::allowedIfHasPermission($account, 'clone eventinstance entity');
    }
    return AccessResult::allowed();
  }

}
