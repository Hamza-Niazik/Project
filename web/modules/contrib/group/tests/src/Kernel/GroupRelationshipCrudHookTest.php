<?php

namespace Drupal\Tests\group\Kernel;

/**
 * Tests the way relationship entities react to entity CRUD events.
 *
 * @group group
 */
class GroupRelationshipCrudHookTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Required to be able to delete accounts. See User::postDelete().
    $this->installSchema('user', ['users_data']);
  }

  /**
   * Tests that a grouped entity deletion triggers relationship deletion.
   */
  public function testGroupedEntityDeletion() {
    $account = $this->createUser();
    $group = $this->createGroup(['type' => $this->createGroupType()->id(), 'uid' => $account->id()]);

    $count = count($group->getRelationships());
    $account->delete();
    $this->assertCount($count - 1, $group->getRelationships(), "Deleting the group owner's account reduces the relationship count by one.");
  }

  /**
   * Tests that an ungrouped entity deletion triggers no relationship deletion.
   */
  public function testUngroupedEntityDeletion() {
    $group = $this->createGroup(['type' => $this->createGroupType()->id()]);

    $count = count($group->getRelationships());
    $this->createUser()->delete();
    $this->assertCount($count, $group->getRelationships(), "Deleting an ungrouped user account does not remove any relationship.");
  }

}
