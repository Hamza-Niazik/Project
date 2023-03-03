<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

/**
 * Checks access for the group_membership relation plugin.
 */
class GroupMembershipAccessControl implements AccessControlInterface {

  use AccessControlTrait;

  /**
   * Constructs a new GroupMembershipAccessControl.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface $parent
   *   The parent access control handler.
   */
  public function __construct(AccessControlInterface $parent) {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsOperation($operation, $target) {
    // While we don't have a dedicated permission for creating a relationship,
    // as you either need the 'join group' permission to join yourself or full
    // member admin rights to add other people, we do actually support it.
    if ($operation === 'create' && $target === 'relationship') {
      return TRUE;
    }
    return $this->parent->supportsOperation($operation, $target);
  }

}
