<?php

namespace Drupal\group;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Loader for wrapped GroupRelationship entities using the 'group_membership' plugin.
 *
 * Seeing as this class is part of the main module, we could have easily put its
 * functionality in GroupRelationshipStorage. We chose not to because other modules
 * won't have that power and we should provide them with an example of how to
 * write such a plugin-specific GroupRelationship loader.
 *
 * Also note that we don't simply return GroupRelationship entities, but wrapped
 * copies of said entities, namely \Drupal\group\GroupMembership. In a future
 * version we should investigate the feasibility of extending GroupRelationship
 * entities rather than wrapping them.
 */
class GroupMembershipLoader implements GroupMembershipLoaderInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user's account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new GroupTypeController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * Gets the relationship storage.
   *
   * @return \Drupal\group\Entity\Storage\GroupRelationshipStorageInterface
   */
  protected function groupRelationshipStorage() {
    return $this->entityTypeManager->getStorage('group_relationship');
  }

  /**
   * Wraps GroupRelationship entities in a GroupMembership object.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface[] $entities
   *   An array of GroupRelationship entities to wrap.
   *
   * @return \Drupal\group\GroupMembership[]
   *   A list of GroupMembership wrapper objects.
   */
  protected function wrapGroupRelationshipEntities($entities) {
    $group_memberships = [];
    foreach ($entities as $group_relationship) {
      $group_memberships[] = new GroupMembership($group_relationship);
    }
    return $group_memberships;
  }

  /**
   * {@inheritdoc}
   */
  public function load(GroupInterface $group, AccountInterface $account) {
    $ids = $this->groupRelationshipStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('gid', $group->id())
      ->condition('entity_id', $account->id())
      ->condition('plugin_id', 'group_membership')
      ->execute();

    if ($ids && $group_relationships = $this->groupRelationshipStorage()->loadMultiple($ids)) {
      $group_memberships = $this->wrapGroupRelationshipEntities($group_relationships);
      return reset($group_memberships);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByGroup(GroupInterface $group, $roles = NULL) {
    $query = $this->groupRelationshipStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('gid', $group->id())
      ->condition('plugin_id', 'group_membership');

    if (isset($roles)) {
      $query->condition('group_roles', (array) $roles, 'IN');
    }

    $ids = $query->execute();
    if ($ids && $group_relationships = $this->groupRelationshipStorage()->loadMultiple($ids)) {
      return $this->wrapGroupRelationshipEntities($group_relationships);
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function loadByUser(AccountInterface $account = NULL, $roles = NULL) {
    if (!isset($account)) {
      $account = $this->currentUser;
    }

    $query = $this->groupRelationshipStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('entity_id', $account->id())
      ->condition('plugin_id', 'group_membership');

    if (isset($roles)) {
      $query->condition('group_roles', (array) $roles, 'IN');
    }

    $ids = $query->execute();
    if ($ids && $group_relationships = $this->groupRelationshipStorage()->loadMultiple($ids)) {
      return $this->wrapGroupRelationshipEntities($group_relationships);
    }

    return [];
  }

}
