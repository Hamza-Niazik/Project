<?php

namespace Drupal\group\Access;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on whether a relationship belongs to the
 * group that was also specified in the route.
 */
class GroupOwnsContentAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $must_own_content = $route->getRequirement('_group_owns_content') === 'TRUE';

    // Don't interfere if no group or relationship was specified.
    $parameters = $route_match->getParameters();
    if (!$parameters->has('group') || !$parameters->has('group_relationship')) {
      return AccessResult::neutral();
    }

    // Don't interfere if the group isn't a real group.
    $group = $parameters->get('group');
    if (!$group instanceof GroupInterface) {
      return AccessResult::neutral();
    }

    // Don't interfere if the relationship isn't a real relationship entity.
    $group_relationship = $parameters->get('group_relationship');
    if (!$group_relationship instanceof GroupRelationshipInterface) {
      return AccessResult::neutral();
    }

    // If we have a group and relationship, see if the owner matches.
    $group_owns_content = $group_relationship->getGroupId() == $group->id();

    // Only allow access if the relationship is owned by the group and
    // _group_owns_content is set to TRUE or the other way around.
    return AccessResult::allowedIf($group_owns_content xor !$must_own_content);
  }

}
