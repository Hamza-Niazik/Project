<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\Tests\group\Traits\NodeTypeCreationTrait;

/**
 * Tests the general access behavior of config wrapper entities.
 *
 * @coversDefaultClass \Drupal\group\Entity\Access\ConfigWrapperAccessControlHandler
 * @group group
 */
class ConfigWrapperAccessControlHandlerTest extends GroupKernelTestBase {

  use NodeTypeCreationTrait;

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

    // Install the node type handling plugin on a group type.
    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->save($storage->createFromPlugin($this->createGroupType(), 'node_type_relation'));
  }

  /**
   * Tests that any operation is denied.
   *
   * @param string $operation
   *   The operation to test.
   *
   * @covers ::checkAccess
   * @dataProvider operationAccessProvider
   */
  public function testOperationAccess($operation) {
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('group_config_wrapper');
    $storage = $this->entityTypeManager->getStorage('group_config_wrapper');
    $wrapper = $storage->create(['bundle' => 'node_type', 'entity_id' => $this->createNodeType()->id()]);
    $this->assertFalse($access_control_handler->access($wrapper, $operation));
  }

  /**
   * Data provider for testOperationAccess().
   *
   * @return array
   *   A list of testOperationAccess method arguments.
   */
  public function operationAccessProvider() {
    $cases = [];
    foreach (['view', 'update', 'delete', 'anything_really'] as $operation) {
      $cases[$operation] = [$operation];
    }
    return $cases;
  }

  /**
   * Tests that the create operation is denied.
   *
   * @covers ::checkCreateAccess
   */
  public function testCreateAccess() {
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('group_config_wrapper');
    $this->assertFalse($access_control_handler->createAccess('node_type'));
  }

}
