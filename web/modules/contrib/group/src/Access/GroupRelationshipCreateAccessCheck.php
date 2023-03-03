<?php

namespace Drupal\group\Access;

use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access for relationship creation.
 */
class GroupRelationshipCreateAccessCheck implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access for relationship creation routes.
   *
   * All routes using this access check should have a group and plugin_id
   * parameter and have the _group_relationship_create_access requirement set to
   * either 'TRUE' or 'FALSE'.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group in which the content should be created.
   * @param string $plugin_id
   *   The group relation type ID to use for the relationship entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, GroupInterface $group, $plugin_id) {
    $needs_access = $route->getRequirement('_group_relationship_create_access') === 'TRUE';

    // We can only get the relationship type ID if the plugin is installed.
    if (!$group->getGroupType()->hasPlugin($plugin_id)) {
      return AccessResult::neutral();
    }

    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('group_relationship');

    // Determine whether the user can create relationships using the plugin.
    $relationship_type_id = $storage->getRelationshipTypeId($group->bundle(), $plugin_id);
    $access = $access_control_handler->createAccess($relationship_type_id, $account, ['group' => $group]);

    // Only allow access if the user can create relationships using the
    // provided plugin or if he doesn't need access to do so.
    return AccessResult::allowedIf($access xor !$needs_access);
  }

}
