<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;

/**
 * Tests for the GroupRelationshipType entity.
 *
 * @group group
 *
 * @coversDefaultClass \Drupal\group\Entity\GroupRelationshipType
 */
class GroupRelationshipTypeTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test_plugin', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
  }

  /**
   * Tests that bundle info is recalculated when needed.
   *
   * @covers ::postSave
   * @uses group_entity_bundle_info
   */
  public function testBundleInfoCacheCleared() {
    $bundle_info = \Drupal::service('entity_type.bundle.info');
    assert($bundle_info instanceof EntityTypeBundleInfo);

    // Assert that there are no bundles. Please note that a content entity type
    // must have at least one bundle so it defaults to the entity type ID.
    $this->assertSame(['group_config_wrapper'], array_keys($bundle_info->getBundleInfo('group_config_wrapper')));

    // Install a config handling plugin on a group type.
    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->save($storage->createFromPlugin($this->createGroupType(), 'node_type_relation'));

    // Assert that the cache was cleared and bundle declared.
    $this->assertSame(['node_type'], array_keys($bundle_info->getBundleInfo('group_config_wrapper')));
  }

}
