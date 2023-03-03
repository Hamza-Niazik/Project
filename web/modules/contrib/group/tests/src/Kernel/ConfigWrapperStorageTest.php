<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\Tests\group\Traits\NodeTypeCreationTrait;

/**
 * Tests the behavior of group config wrapper storage handler.
 *
 * @coversDefaultClass \Drupal\group\Entity\Storage\ConfigWrapperStorage
 * @group group
 */
class ConfigWrapperStorageTest extends GroupKernelTestBase {

  use NodeTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test_plugin', 'node'];

  /**
   * The group config wrapper storage handler.
   *
   * @var \Drupal\group\Entity\Storage\ConfigWrapperStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->storage = $this->entityTypeManager->getStorage('group_config_wrapper');

    // Install the node type handling plugin on a group type.
    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->save($storage->createFromPlugin($this->createGroupType(), 'node_type_relation'));
  }

  /**
   * Tests the support for an entity.
   *
   * @covers ::supportsEntity
   */
  public function testSupportsEntity() {
    $this->assertTrue($this->storage->supportsEntity($this->createNodeType()));
    $this->assertFalse($this->storage->supportsEntity($this->createGroupType()));
  }

  /**
   * Tests the support for an entity type ID.
   *
   * @covers ::supportsEntityTypeId
   */
  public function testSupportsEntityTypeId() {
    $this->assertTrue($this->storage->supportsEntityTypeId('node_type'));
    $this->assertFalse($this->storage->supportsEntityTypeId('group_type'));
  }

  /**
   * Tests the creation of a ConfigWrapper entity.
   *
   * @covers ::wrapEntityId
   */
  public function testWrapEntityId() {
    $node_type = $this->createNodeType();
    $wrapper = $this->storage->wrapEntityId('node_type', $node_type->id());
    $this->assertSame($node_type->id(), $wrapper->getConfigEntityId());
  }

  /**
   * Tests the loading of a ConfigWrapper entity.
   *
   * @covers ::wrapEntityId
   */
  public function testWrapUnsupportedEntityId() {
    $group_type = $this->createGroupType();
    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Trying to wrap an unsupported entity of type "group_type".');
    $this->storage->wrapEntityId('group_type', $group_type->id());
  }

  /**
   * Tests that nothing is wrapped if flag is set to only load.
   *
   * @covers ::wrapEntityId
   * @depends testWrapEntityId
   */
  public function testWrapEntityIdNoCreate() {
    $node_type = $this->createNodeType();
    $this->assertFalse($this->storage->wrapEntityId('node_type', $node_type->id(), FALSE));
    $this->storage->wrapEntity($node_type);
    $this->assertNotFalse($this->storage->wrapEntityId('node_type', $node_type->id(), FALSE));
  }

  /**
   * Tests the loading of a ConfigWrapper entity.
   *
   * @covers ::wrapEntityId
   * @depends testWrapEntityId
   */
  public function testWrapWrappedEntityId() {
    $node_type = $this->createNodeType();
    $wrapper_a = $this->storage->wrapEntityId('node_type', $node_type->id());
    $wrapper_b = $this->storage->wrapEntityId('node_type', $node_type->id());
    $this->assertSame($wrapper_a->id(), $wrapper_b->id());
  }

  /**
   * Tests the creation of a ConfigWrapper entity.
   *
   * @covers ::wrapEntity
   */
  public function testWrapEntity() {
    $node_type = $this->createNodeType();
    $wrapper = $this->storage->wrapEntity($node_type);
    $this->assertSame($node_type->id(), $wrapper->getConfigEntityId());
  }

  /**
   * Tests the loading of a ConfigWrapper entity.
   *
   * @covers ::wrapEntity
   */
  public function testWrapUnsupportedEntity() {
    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Trying to wrap an unsupported entity of type "group_type".');
    $this->storage->wrapEntity($this->createGroupType());
  }

  /**
   * Tests that nothing is wrapped if flag is set to only load.
   *
   * @covers ::wrapEntity
   * @depends testWrapEntity
   */
  public function testWrapEntityNoCreate() {
    $node_type = $this->createNodeType();
    $this->assertFalse($this->storage->wrapEntity($node_type, FALSE));
    $this->storage->wrapEntity($node_type);
    $this->assertNotFalse($this->storage->wrapEntity($node_type, FALSE));
  }

  /**
   * Tests the loading of a ConfigWrapper entity.
   *
   * @covers ::wrapEntity
   * @depends testWrapEntity
   */
  public function testWrapWrappedEntity() {
    $node_type = $this->createNodeType();
    $wrapper_a = $this->storage->wrapEntity($node_type);
    $wrapper_b = $this->storage->wrapEntity($node_type);
    $this->assertSame($wrapper_a->id(), $wrapper_b->id());
  }

}
