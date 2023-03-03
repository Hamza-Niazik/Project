<?php

namespace Drupal\Tests\group\Kernel\QueryAlter;

use Drupal\group\Entity\GroupTypeInterface;

/**
 * Tests that Group properly checks access for grouped entities.
 *
 * @coversDefaultClass \Drupal\group\QueryAccess\EntityQueryAlter
 * @group group
 */
class EntityQueryAlterTest extends EntityQueryAlterTestBase {

  /**
   * {@inheritdoc}
   */
  protected $entityTypeId = 'entity_test_with_owner';

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'entity_test_relation';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig('group_test_plugin');
    $this->installEntitySchema('entity_test_with_owner');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpContent(GroupTypeInterface $group_type) {
    parent::setUpContent($group_type);

    // Add two entities, one of which belongs to a group.
    $this->createTestEntity();
    $group = $this->createGroup(['type' => $group_type->id()]);
    $group->addRelationship($this->createTestEntity(['type' => 'page']), 'entity_test_relation');
    return $group;
  }

  /**
   * Creates a test entity.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\entity_test\Entity\EntityTest
   *   The created test entity.
   */
  protected function createTestEntity(array $values = []) {
    $storage = $this->entityTypeManager->getStorage('entity_test_with_owner');
    $test_entity = $storage->create($values + [
      'name' => $this->randomString(),
      'type' => $this->randomMachineName(),
    ]);
    $test_entity->enforceIsNew();
    $storage->save($test_entity);
    return $test_entity;
  }

}
