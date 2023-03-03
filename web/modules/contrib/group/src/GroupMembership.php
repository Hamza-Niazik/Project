<?php

namespace Drupal\group;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\Storage\GroupRoleStorageInterface;

/**
 * Wrapper class for a GroupRelationship entity representing a membership.
 *
 * Should be loaded through the 'group.membership_loader' service.
 *
 * @todo Consider refactoring, e.g.: getRoles() seems pointless.
 */
class GroupMembership implements CacheableDependencyInterface {

  /**
   * The relationship entity to wrap.
   *
   * @var \Drupal\group\Entity\GroupRelationshipInterface
   */
  protected $groupRelationship;

  /**
   * Constructs a new GroupMembership.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface $group_relationship
   *   The relationship entity representing the membership.
   *
   * @throws \Exception
   *   Exception thrown when trying to instantiate this class with a
   *   GroupRelationship entity that was not based on the GroupMembership content
   *   enabler plugin.
   */
  public function __construct(GroupRelationshipInterface $group_relationship) {
    if ($group_relationship->getRelationshipType()->getPluginId() == 'group_membership') {
      $this->groupRelationship = $group_relationship;
    }
    else {
      throw new \Exception('Trying to create a GroupMembership from an incompatible GroupRelationship entity.');
    }
  }

  /**
   * Returns the fieldable GroupRelationship entity for the membership.
   *
   * @return \Drupal\group\Entity\GroupRelationshipInterface
   */
  public function getGroupRelationship() {
    return $this->groupRelationship;
  }

  /**
   * Returns the group for the membership.
   *
   * @return \Drupal\group\Entity\GroupInterface
   */
  public function getGroup() {
    return $this->groupRelationship->getGroup();
  }

  /**
   * Returns the user for the membership.
   *
   * @return \Drupal\user\UserInterface
   */
  public function getUser() {
    return $this->groupRelationship->getEntity();
  }

  /**
   * Returns the group roles for the membership.
   *
   * @param boolean $include_synchronized
   *   (optional) Whether to include the synchronized roles from the outsider or
   *   insider scope. Defaults to TRUE.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]
   *   An array of group roles, keyed by their ID.
   */
  public function getRoles($include_synchronized = TRUE) {
    $group_role_storage = \Drupal::entityTypeManager()->getStorage('group_role');
    assert($group_role_storage instanceof GroupRoleStorageInterface);
    return $group_role_storage->loadByUserAndGroup($this->getUser(), $this->getGroup(), $include_synchronized);
  }

  /**
   * Checks whether the member has a permission.
   *
   * @param string $permission
   *   The permission to check for.
   *
   * @return bool
   *   Whether the member has the requested permission.
   */
  public function hasPermission($permission) {
    return $this->groupPermissionChecker()->hasPermissionInGroup($permission, $this->getUser(), $this->getGroup());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->getGroupRelationship()->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->getGroupRelationship()->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->getGroupRelationship()->getCacheMaxAge();
  }

  /**
   * Gets the group permission checker.
   *
   * @return \Drupal\group\Access\GroupPermissionCheckerInterface
   */
  protected function groupPermissionChecker() {
    return \Drupal::service('group_permission.checker');
  }

}
