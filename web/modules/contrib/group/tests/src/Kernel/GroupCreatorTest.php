<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\Storage\GroupRoleStorageInterface;
use Drupal\group\PermissionScopeInterface;

/**
 * Tests the behavior of group creators.
 *
 * @todo Move to Drupal\Tests\group\Functional\GroupCreatorWizardTest when we
 *   remove the functionality that auto-creates creator memberships while saving
 *   a new group programmatically.
 *
 * @group group
 */
class GroupCreatorTest extends GroupKernelTestBase {

  /**
   * Tests that a group creator is not automatically made a member.
   */
  public function testCreatorDoesNotGetMembership() {
    $group = $this->createGroup(['type' => $this->createGroupType(['creator_membership' => FALSE])->id()]);
    $this->assertFalse($group->getMember($this->getCurrentUser()), 'Membership could not be loaded for the group creator.');
  }

  /**
   * Tests that a group creator is automatically a member.
   */
  public function testCreatorGetsMembership() {
    $group = $this->createGroup(['type' => $this->createGroupType()->id()]);
    $this->assertNotFalse($group->getMember($this->getCurrentUser()), 'Membership could be loaded for the group creator.');
    $this->assertCount(0, $this->getCreatorRoles($this->getCurrentUser(), $group), 'Membership has zero roles.');
  }

  /**
   * Tests that a group creator gets the configured roles.
   *
   * @depends testCreatorGetsMembership
   */
  public function testCreatorRoles() {
    $group_type = $this->createGroupType();
    $group_role = $this->createGroupRole([
      'group_type' => $group_type->id(),
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
    ]);
    $group_type->set('creator_roles', [$group_role->id()]);
    $group_type->save();

    $group = $this->createGroup(['type' => $group_type->id()]);
    $group_roles = $this->getCreatorRoles($this->getCurrentUser(), $group);

    $this->assertCount(1, $group_roles, 'Membership has one role.');
    $this->assertEquals($group_role->id(), reset($group_roles)->id(), 'Membership has the custom role.');
  }

  /**
   * Gets the roles for the group creator account's membership.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to load the group role entities for.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity to find the user's role entities in.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]
   *   The group roles matching the criteria.
   */
  protected function getCreatorRoles(AccountInterface $account, GroupInterface $group) {
    $group_role_storage = $this->entityTypeManager->getStorage('group_role');
    assert($group_role_storage instanceof GroupRoleStorageInterface);
    return $group_role_storage->loadByUserAndGroup($account, $group, FALSE);
  }

}
