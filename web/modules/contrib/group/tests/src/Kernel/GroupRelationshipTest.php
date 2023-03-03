<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\group\Entity\GroupRelationship;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\Tests\group\Traits\NodeTypeCreationTrait;

/**
 * Tests for the GroupRelationship entity.
 *
 * @group group
 *
 * @coversDefaultClass \Drupal\group\Entity\GroupRelationship
 */
class GroupRelationshipTest extends GroupKernelTestBase {

  use NodeTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test', 'group_test_plugin', 'node'];

  /**
   * Tests that entity url templates are functional.
   *
   * @covers ::urlRouteParameters
   */
  public function testUrlRouteParameters() {
    $group_type = $this->createGroupType();
    $group = $this->createGroup(['type' => $group_type->id()]);

    $account = $this->createUser();
    $group->addMember($account);
    $group_relationship = $group->getMember($account)->getGroupRelationship();

    // Canonical.
    $expected = "/group/{$group->id()}/content/{$group_relationship->id()}";
    $this->assertEquals($expected, $group_relationship->toUrl()->toString());

    // Add form.
    $expected = "/group/{$group->id()}/content/add/group_membership";
    $this->assertEquals($expected, $group_relationship->toUrl('add-form')->toString());

    // Add page.
    $expected = "/group/{$group->id()}/content/add";
    $this->assertEquals($expected, $group_relationship->toUrl('add-page')->toString());

    // Collection.
    $expected = "/group/{$group->id()}/content";
    $this->assertEquals($expected, $group_relationship->toUrl('collection')->toString());

    // Create form.
    $expected = "/group/{$group->id()}/content/create/group_membership";
    $this->assertEquals($expected, $group_relationship->toUrl('create-form')->toString());

    // Create page.
    $expected = "/group/{$group->id()}/content/create";
    $this->assertEquals($expected, $group_relationship->toUrl('create-page')->toString());

    // Delete form.
    $expected = "/group/{$group->id()}/content/{$group_relationship->id()}/delete";
    $this->assertEquals($expected, $group_relationship->toUrl('delete-form')->toString());

    // Edit form.
    $expected = "/group/{$group->id()}/content/{$group_relationship->id()}/edit";
    $this->assertEquals($expected, $group_relationship->toUrl('edit-form')->toString());
  }

  /**
   * Tests that after adding an entity to a group, it gets saved again.
   *
   * @covers ::postSave
   *
   * @see group_test_user_update()
   */
  public function testSubjectResaved() {
    $changed = 123456789;
    $account = $this->createUser(['changed' => $changed]);

    $group = $this->createGroup(['type' => $this->createGroupType()->id()]);
    $group->addRelationship($account, 'group_membership');

    // All users whose changed time was set to 123456789 get their changed time
    // set to 530496000 in group_test_user_update() when the account is updated.
    $account_unchanged = $this->entityTypeManager->getStorage('user')->loadUnchanged($account->id());
    $this->assertEquals(530496000, $account_unchanged->getChangedTime(), 'Account was saved as part of being added to a group.');
  }

  /**
   * Tests the retrieval of the grouped entity.
   *
   * @covers ::getEntity
   * @covers ::getEntityId
   */
  public function testGetEntity() {
    // Create a group type and enable relating users and node types.
    $group_type = $this->createGroupType();

    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->createFromPlugin($group_type, 'user_relation')->save();
    $storage->createFromPlugin($group_type, 'node_type_relation')->save();
    $group = $this->createGroup(['type' => $group_type->id()]);

    $account = $this->createUser();
    $group_relationship = $group->addRelationship($account, 'user_relation');
    $this->assertEquals($account->id(), $group_relationship->getEntity()->id());
    $this->assertEquals($account->id(), $group_relationship->getEntityId());
    $this->assertEquals('user', $group_relationship->getEntity()->getEntityTypeId());

    $node_type = $this->createNodeType();
    $group_relationship = $group->addRelationship($node_type, 'node_type_relation');
    $this->assertEquals('node_type', $group_relationship->getEntity()->getEntityTypeId());
    $this->assertEquals($node_type->id(), $group_relationship->getEntity()->id());
    $this->assertEquals($node_type->id(), $group_relationship->getEntityId());
  }

  /**
   * Tests that custom list cache tags are properly invalidated.
   *
   * @covers ::getListCacheTagsToInvalidate
   */
  public function testListCacheTagInvalidation() {
    $cache = \Drupal::cache();

    // Create a group type and enable relating users.
    $group_type = $this->createGroupType();

    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->createFromPlugin($group_type, 'user_relation')->save();

    // Create a group and user to check the cache tags for.
    $test_group = $this->createGroup(['type' => $group_type->id()]);
    $test_group_id = $test_group->id();
    $test_account = $this->createUser();
    $test_account_id = $test_account->id();

    // Create an extra group and account to test with.
    $extra_group = $this->createGroup(['type' => $group_type->id()]);
    $extra_account = $this->createUser();

    $scenarios = [
      // Create a list for specific group, any entity, any plugin.
      'group_relationship' => ["group_relationship_list:group:$test_group_id"],
      // Create a list for any group, specific entity, any plugin.
      'relationship_groups' => ["group_relationship_list:entity:$test_account_id"],
      // Create a list for any group, any entity, specific plugin.
      'all_memberships' => ["group_relationship_list:plugin:group_membership"],
      // Create a list for specific group, any entity, specific plugin.
      'group_memberships' => ["group_relationship_list:plugin:group_membership:group:$test_group_id"],
      // Create a list for any group, specific entity, specific plugin.
      'user_memberships' => ["group_relationship_list:plugin:group_membership:entity:$test_account_id"],
    ];
    foreach ($scenarios as $cid => $cache_tags) {
      $cache->set($cid, 'foo', CacheBackendInterface::CACHE_PERMANENT, $cache_tags);
    }

    // Add another user to another group and verify cache entries.
    $extra_group->addRelationship($extra_account, 'user_relation');
    $this->assertNotFalse($cache->get('group_relationship'), 'List for specific group, any entity, any plugin found.');
    $this->assertNotFalse($cache->get('relationship_groups'), 'List for any group, specific entity, any plugin found.');
    $this->assertNotFalse($cache->get('all_memberships'), 'List for any group, any entity, specific plugin found.');
    $this->assertNotFalse($cache->get('group_memberships'), 'List for specific group, any entity, specific plugin found.');
    $this->assertNotFalse($cache->get('user_memberships'), 'List for any group, specific entity, specific plugin found.');

    // Relate another user to the group and verify cache entries.
    $test_group->addRelationship($extra_account, 'user_relation');
    $this->assertFalse($cache->get('group_relationship'), 'List for specific group, any entity, any plugin cleared.');
    $this->assertNotFalse($cache->get('relationship_groups'), 'List for any group, specific entity, any plugin found.');
    $this->assertNotFalse($cache->get('all_memberships'), 'List for any group, any entity, specific plugin found.');
    $this->assertNotFalse($cache->get('group_memberships'), 'List for specific group, any entity, specific plugin found.');
    $this->assertNotFalse($cache->get('user_memberships'), 'List for any group, specific entity, specific plugin found.');

    // Relate the user to another group and verify cache entries.
    $extra_group->addRelationship($test_account, 'user_relation');
    $this->assertFalse($cache->get('relationship_groups'), 'List for any group, specific entity, any plugin cleared.');
    $this->assertNotFalse($cache->get('all_memberships'), 'List for any group, any entity, specific plugin found.');
    $this->assertNotFalse($cache->get('group_memberships'), 'List for specific group, any entity, specific plugin found.');
    $this->assertNotFalse($cache->get('user_memberships'), 'List for any group, specific entity, specific plugin found.');

    // Add any user as a member to any group and verify cache entries.
    $extra_group->addMember($extra_account);
    $this->assertFalse($cache->get('all_memberships'), 'List for any group, any entity, specific plugin cleared.');
    $this->assertNotFalse($cache->get('group_memberships'), 'List for specific group, any entity, specific plugin found.');
    $this->assertNotFalse($cache->get('user_memberships'), 'List for any group, specific entity, specific plugin found.');

    // Add any user as a member to the group and verify cache entries.
    $test_group->addMember($extra_account);
    $this->assertFalse($cache->get('group_memberships'), 'List for specific group, any entity, specific plugin cleared.');
    $this->assertNotFalse($cache->get('user_memberships'), 'List for any group, specific entity, specific plugin found.');

    // Add the user as a member to any group and verify cache entries.
    $extra_group->addMember($test_account);
    $this->assertFalse($cache->get('user_memberships'), 'List for any group, specific entity, specific plugin cleared.');

    // Set the cache again and verify if we add the user to the group.
    foreach ($scenarios as $cid => $cache_tags) {
      $cache->set($cid, 'foo', CacheBackendInterface::CACHE_PERMANENT, $cache_tags);
    }
    $test_group->addMember($test_account);
    $this->assertFalse($cache->get('group_relationship'), 'List for specific group, any entity, any plugin cleared.');
    $this->assertFalse($cache->get('relationship_groups'), 'List for any group, specific entity, any plugin cleared.');
    $this->assertFalse($cache->get('all_memberships'), 'List for any group, any entity, specific plugin cleared.');
    $this->assertFalse($cache->get('group_memberships'), 'List for specific group, any entity, specific plugin cleared.');
    $this->assertFalse($cache->get('user_memberships'), 'List for any group, specific entity, specific plugin cleared.');
  }

  /**
   * Tests that custom list cache tags are properly set for config entities.
   *
   * @covers ::getListCacheTagsToInvalidate
   * @depends testListCacheTagInvalidation
   */
  public function testGetListCacheTagsToInvalidateForConfig() {
    // Create a group type and enable relating node types.
    $group_type = $this->createGroupType();

    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->createFromPlugin($group_type, 'node_type_relation')->save();

    $group = $this->createGroup(['type' => $group_type->id()]);
    $node_type = $this->createNodeType();

    $group_relationship = $group->addRelationship($node_type, 'node_type_relation');
    $expected = [
      'group_relationship_list',
      'group_relationship_list:' . $group_relationship->bundle(),
      'group_relationship_list:group:' . $group->id(),
      'group_relationship_list:entity:' . $node_type->id(),
      'group_relationship_list:plugin:node_type_relation',
      'group_relationship_list:plugin:node_type_relation:group:' . $group->id(),
      'group_relationship_list:plugin:node_type_relation:entity:' . $node_type->id(),
    ];
    // We can call this method because we made it public.
    assert($group_relationship instanceof GroupRelationship);
    $this->assertSame($expected, $group_relationship->getListCacheTagsToInvalidate());
  }

}
