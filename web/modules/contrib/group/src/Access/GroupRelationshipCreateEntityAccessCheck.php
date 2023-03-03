<?php

namespace Drupal\group\Access;

use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access for relationship target entity creation.
 */
class GroupRelationshipCreateEntityAccessCheck implements AccessInterface {

  /**
   * Checks access for relationship target entity creation routes.
   *
   * All routes using this access check should have a group and plugin_id
   * parameter and have the _group_relationship_create_entity_access requirement set
   * to either 'TRUE' or 'FALSE'.
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
    $needs_access = $route->getRequirement('_group_relationship_create_entity_access') === 'TRUE';

    // We can only get the relationship type ID if the plugin is installed.
    if (!$group->getGroupType()->hasPlugin($plugin_id)) {
      return AccessResult::neutral();
    }

    $plugin_manager = \Drupal::service('group_relation_type.manager');
    assert($plugin_manager instanceof GroupRelationTypeManagerInterface);
    $access_handler = $plugin_manager->getAccessControlHandler($plugin_id);
    $access = $access_handler->entityCreateAccess($group, $account);

    // Only allow access if the user can create relationship target entities
    // using the provided plugin or if he doesn't need access to do so.
    return AccessResult::allowedIf($access xor !$needs_access);
  }

}
