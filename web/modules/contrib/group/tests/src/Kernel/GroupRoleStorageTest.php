<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;

/**
 * Tests the behavior of group role storage handler.
 *
 * @coversDefaultClass \Drupal\group\Entity\Storage\GroupRoleStorage
 * @group group
 */
class GroupRoleStorageTest extends GroupKernelTestBase {

  /**
   * The group to run tests with.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * The user to get added to the test group.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->group = $this->createGroup(['type' => $this->createGroupType()->id()]);
    $this->account = $this->createUser();
  }

  /**
   * Tests the loading of group roles by user and group.
   *
   * @covers ::loadByUserAndGroup
   */
  public function testLoadByUserAndGroup() {
    $outsider_role = $this->createGroupRole([
      'group_type' => $this->group->bundle(),
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
    ]);
    $this->compareMemberRoles([], FALSE, 'User has no individual group roles as they are not a member.');
    $this->compareMemberRoles([$outsider_role->id()], TRUE, 'User initially has synchronized outsider role.');

    // Create and assign a random Drupal role.
    $storage = $this->entityTypeManager->getStorage('user_role');
    $user_role = $storage->create([
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ]);
    $storage->save($user_role);
    $this->account->addRole($user_role->id());
    $this->account->save();

    // Create an outsider role that synchronizes with the Drupal role.
    $group_role = $this->createGroupRole([
      'group_type' => $this->group->bundle(),
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => $user_role->id(),
    ]);
    $this->compareMemberRoles([], FALSE, 'User has no individual group roles as they are not a member.');
    $this->compareMemberRoles([$outsider_role->id(), $group_role->id()], TRUE, 'User has synchronized outsider roles.');

    // From this point on we test with the user as a member.
    $insider_role = $this->createGroupRole([
      'group_type' => $this->group->bundle(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
    ]);
    $this->group->addMember($this->account);
    $this->compareMemberRoles([], FALSE, 'User still has no explicit group roles.');
    $this->compareMemberRoles([$insider_role->id()], TRUE, 'User has synchronized insider role now that they have joined the group.');

    // Grant the member a new group role and check the storage.
    $individual_role = $this->createGroupRole([
      'group_type' => $this->group->bundle(),
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
    ]);
    // @todo This displays a desperate need for addRole() and removeRole().
    $membership = $this->group->getMember($this->account)->getGroupRelationship();
    $membership->group_roles[] = $individual_role->id();
    $membership->save();
    $this->compareMemberRoles([$individual_role->id()], FALSE, 'User has the individual group role.');
    $this->compareMemberRoles([$individual_role->id(), $insider_role->id()], TRUE, 'User also has synchronized insider role.');
  }

  /**
   * Asserts that the test user's group roles match a provided list of IDs.
   *
   * @param string[] $expected
   *   The group role IDs we expect the user to have.
   * @param bool $include_synchronized
   *   Whether to include synchronized group roles.
   * @param string $message
   *   The message to display for the assertion.
   */
  protected function compareMemberRoles($expected, $include_synchronized, $message) {
    $storage = $this->entityTypeManager->getStorage('group_role');
    $group_roles = $storage->loadByUserAndGroup($this->account, $this->group, $include_synchronized);
    $this->assertEqualsCanonicalizing($expected, array_keys($group_roles), $message);
  }

}
