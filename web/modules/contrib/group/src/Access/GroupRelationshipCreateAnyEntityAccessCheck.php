<?php

namespace Drupal\group\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access for relationship target entity creation.
 */
class GroupRelationshipCreateAnyEntityAccessCheck implements AccessInterface {

  /**
   * Checks access for relationship target entity creation routes.
   *
   * All routes using this access check should have a group parameter and have
   * the _group_relationship_create_any_entity_access requirement set to 'TRUE' or
   * 'FALSE'.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group in which the content should be created.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, GroupInterface $group) {
    $needs_access = $route->getRequirement('_group_relationship_create_any_entity_access') === 'TRUE';
    $base_plugin_id = $route->getDefault('base_plugin_id');

    $plugin_manager = \Drupal::service('group_relation_type.manager');
    assert($plugin_manager instanceof GroupRelationTypeManagerInterface);
    $plugin_ids = $plugin_manager->getGroupTypePluginMap()[$group->bundle()];

    // Find out which plugins allow the user to create a target entity.
    foreach ($plugin_ids as $plugin_id) {
      // Filter on derivatives if a base plugin ID was provided.
      if ($base_plugin_id && strpos($plugin_id, $base_plugin_id . ':') !== 0) {
        continue;
      }

      $access_handler = $plugin_manager->getAccessControlHandler($plugin_id);
      if ($access_handler->entityCreateAccess($group, $account, TRUE)->isAllowed()) {
        // Allow access if the route flag was set to 'TRUE'.
        return AccessResult::allowedIf($needs_access);
      }
    }

    // If we got this far, it means the user could not create any content in the
    // group. So only allow access if the route flag was set to 'FALSE'.
    return AccessResult::allowedIf(!$needs_access);
  }

}
