<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;

/**
 * Tests the creation of group type entities.
 *
 * @coversDefaultClass \Drupal\group\Entity\GroupType
 * @group group
 */
class GroupTypeCreateTest extends GroupKernelTestBase {

  /**
   * Tests special behavior during group type creation.
   *
   * @covers ::postSave
   */
  public function testCreate() {
    $relationship_type_storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($relationship_type_storage instanceof GroupRelationshipTypeStorageInterface);
    $this->assertCount(0, $relationship_type_storage->loadByEntityTypeId('user'));

    // Check that the group type was created and saved properly.
    $group_type_storage = $this->entityTypeManager->getStorage('group_type');
    $group_type = $group_type_storage->create([
      'id' => 'dummy',
      'label' => 'Dummy',
      'description' => $this->randomMachineName(),
    ]);

    assert($group_type instanceof GroupTypeInterface);
    $this->assertInstanceOf(GroupTypeInterface::class, $group_type);
    $this->assertEquals(SAVED_NEW, $group_type_storage->save($group_type), 'Group type was saved successfully.');

    // Check that enforced plugins were installed.
    $this->assertCount(1, $relationship_type_storage->loadByEntityTypeId('user'));
    $relationship_type = $relationship_type_storage->load(
      $relationship_type_storage->getRelationshipTypeId($group_type->id(), 'group_membership')
    );
    $this->assertNotNull($relationship_type, 'Enforced plugins were installed on the group type.');
  }

}
