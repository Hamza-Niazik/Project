<?php

namespace Drupal\group\Entity\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access controller for the Group entity.
 *
 * @see \Drupal\group\Entity\Group.
 */
class GroupAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * Map of revision operations.
   *
   * Keys contain revision operations, where values are an array containing the
   * group permission and entity operation. The group permission is checked to
   * see if you have access at all.
   *
   * Entity operation is used to determine additional access, e.g for the
   * 'delete revision' operation, an account must also have access to the
   * 'delete' operation on the same group.
   */
  protected const REVISION_OPERATION_MAP = [
    'view all revisions' => ['view all group revisions', 'view'],
    'view revision' => ['view group revisions', 'view'],
    'revert revision' => ['revert group revisions', 'update'],
    'delete revision' => ['delete group revisions', 'delete'],
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = new static($entity_type);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    assert($entity instanceof GroupInterface);
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          $access_result = GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'view any unpublished group');
          if (!$access_result->isAllowed() && $account->isAuthenticated() && $account->id() === $entity->getOwnerId()) {
            $access_result = GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'view own unpublished group')->cachePerUser();
          }
        }
        else {
          $access_result = GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'view group');
        }

        // The access result might change if group status changes.
        return $access_result->addCacheableDependency($entity);

      case 'update':
        return GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'edit group');

      case 'delete':
        return GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'delete group');
    }

    [$revision_permission, $entity_operation] = static::REVISION_OPERATION_MAP[$operation] ?? [
      NULL,
      NULL,
    ];

    // Revision operations.
    if ($revision_permission) {
      $cacheability = (new CacheableMetadata())->addCacheContexts(['user.group_permissions']);

      // If user doesn't have the required permission, quit.
      if (!$entity->hasPermission($revision_permission, $account)) {
        return AccessResult::forbidden()->addCacheableDependency($cacheability);
      }

      // If the user has the view all revisions permission and this is the view
      // all revisions operation then we can allow access.
      if ($operation === 'view all revisions') {
        return AccessResult::allowed()->addCacheableDependency($cacheability);
      }

      // If this is the default revision, return access denied for revert or
      // delete operations. At this point, we need to add the entity as a cache
      // dependency because if it changes, the result might change.
      $cacheability->addCacheableDependency($entity);
      if ($entity->isDefaultRevision() && ($operation === 'revert revision' || $operation === 'delete revision')) {
        return AccessResult::forbidden()->addCacheableDependency($cacheability);
      }

      // First check the access to the default revision and, if the passed in
      // group is not the default revision, check access to that too.
      $storage = $this->entityTypeManager->getStorage('group');
      $access = $this->access($storage->load($entity->id()), $entity_operation, $account, TRUE);
      if (!$entity->isDefaultRevision()) {
        $access = $access->andIf($this->access($entity, $entity_operation, $account, TRUE));
      }

      return $access->addCacheableDependency($cacheability);
    }

    // The Group module's ideology is that if you want to do something to a
    // group, you need Group to explicitly allow access or else the result will
    // be forbidden. Having said that, if we do not support an operation yet,
    // it's probably nicer to return neutral here. This way, any module that
    // exposes new operations will work as intended AND NOT HAVE GROUP ACCESS
    // CHECKS until Group specifically implements said operations.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create ' . $entity_bundle . ' group');
  }

}
