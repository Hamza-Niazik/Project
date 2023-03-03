<?php

namespace Drupal\Tests\group\Kernel\Views;

use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;

/**
 * Tests the group_relationship_to_entity relationship handler.
 *
 * @see \Drupal\group\Plugin\views\relationship\GroupRelationshipToEntity
 *
 * @group group
 */
class GroupRelationshipToEntityRelationshipTest extends GroupViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test_plugin', 'node'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_group_relationship_to_entity_relationship'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);
    $this->installEntitySchema('node');

    // Enable the user_relation plugin on the test group type.
    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->createFromPlugin($this->groupType, 'user_relation')->save();
  }

  /**
   * Tests that a regular user is not returned by the view.
   */
  public function testRegularUserIsNotListed() {
    $this->createUser();
    $this->assertEquals(0, count($this->getViewResults()), 'The view does not show regular users.');
  }

  /**
   * Tests that a group's owner (default member) is returned by the view.
   */
  public function testGroupOwnerIsListed() {
    $this->createGroup();
    $this->assertEquals(1, count($this->getViewResults()), 'The view displays the user for the default member.');
  }

  /**
   * Tests that an extra group member is returned by the view.
   *
   * @depends testGroupOwnerIsListed
   */
  public function testAddedMemberIsListed() {
    $group = $this->createGroup();
    $group->addMember($this->createUser());
    $this->assertEquals(2, count($this->getViewResults()), 'The view displays the users for both the default and the added member.');
  }

  /**
   * Tests that any other relationship is not returned by the view.
   *
   * @depends testGroupOwnerIsListed
   */
  public function testOtherContentIsNotListed() {
    $group = $this->createGroup();
    $group->addRelationship($this->createUser(), 'user_relation');
    $this->assertEquals(1, count($this->getViewResults()), 'The view only displays the user for default member and not the one that was simply related.');
  }

}
