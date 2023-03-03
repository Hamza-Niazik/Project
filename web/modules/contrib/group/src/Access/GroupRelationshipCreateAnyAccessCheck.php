<?php

namespace Drupal\group\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupRelationshipTypeInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access for relationship creation.
 */
class GroupRelationshipCreateAnyAccessCheck implements AccessInterface {

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
   * All routes using this access check should have a group parameter and have
   * the _group_relationship_create_any_access requirement set to 'TRUE' or 'FALSE'.
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
    $needs_access = $route->getRequirement('_group_relationship_create_any_access') === 'TRUE';
    $base_plugin_id = $route->getDefault('base_plugin_id');

    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('group_relationship');
    $storage = $this->entityTypeManager->getStorage('group_relationship_type');

    // Find out which relationship types the user has access to create.
    foreach ($storage->loadByProperties(['group_type' => $group->bundle()]) as $relationship_type) {
      assert($relationship_type instanceof GroupRelationshipTypeInterface);

      // Filter on derivatives if a base plugin ID was provided.
      if ($base_plugin_id && $relationship_type->getPlugin()->getBaseId() !== $base_plugin_id) {
        continue;
      }

      if ($access_control_handler->createAccess($relationship_type->id(), $account, ['group' => $group])) {
        // Allow access if the route flag was set to 'TRUE'.
        return AccessResult::allowedIf($needs_access);
      }
    }

    // If we got this far, it means the user could not create any content in the
    // group. So only allow access if the route flag was set to 'FALSE'.
    return AccessResult::allowedIf(!$needs_access);
  }

}
