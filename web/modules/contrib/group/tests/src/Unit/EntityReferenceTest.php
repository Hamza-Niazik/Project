<?php

namespace Drupal\Tests\group\Unit;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldTypePluginManager;
use Drupal\group\Plugin\Group\Relation\GroupRelationType;
use Drupal\group\Plugin\Group\RelationHandlerDefault\EntityReference;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the default group relation entity_reference handler.
 *
 * @coversDefaultClass \Drupal\group\Plugin\Group\RelationHandlerDefault\EntityReference
 * @group group
 */
class EntityReferenceTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $field_type_manager = $this->prophesize(FieldTypePluginManager::class);
    $field_type_manager->getDefaultStorageSettings('entity_reference')->willReturn([]);
    $field_type_manager->getDefaultFieldSettings('entity_reference')->willReturn([]);

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('plugin.manager.field.field_type')->willReturn($field_type_manager->reveal());
    \Drupal::setContainer($container->reveal());
  }

  /**
   * Tests the field modifier.
   *
   * @covers ::configureField
   */
  public function testConfigureField() {
    $base_field_definition = BaseFieldDefinition::create('entity_reference');
    $group_relation_type = new GroupRelationType([
      'reference_label' => 'orange',
      'reference_description' => 'cherry',
      'entity_type_id' => 'apple',
      'entity_bundle' => 'lemon',
    ]);

    $entity_reference_handler = new EntityReference();
    $entity_reference_handler->init('foo', $group_relation_type);
    $entity_reference_handler->configureField($base_field_definition);

    $this->assertEquals($group_relation_type->getEntityReferenceLabel(), $base_field_definition->getLabel());
    $this->assertEquals($group_relation_type->getEntityReferenceDescription(), $base_field_definition->getDescription());
    $this->assertEquals($group_relation_type->getEntityTypeId(), $base_field_definition->getSetting('target_type'));
    $this->assertEquals(['target_bundles' => [$group_relation_type->getEntityBundle()]], $base_field_definition->getSetting('handler_settings'));
  }

  /**
   * Tests the field modifier.
   *
   * @covers ::configureField
   */
  public function testConfigureFieldForConfig() {
    $base_field_definition = BaseFieldDefinition::create('entity_reference');
    $group_relation_type = new GroupRelationType([
      'reference_label' => 'orange',
      'reference_description' => 'cherry',
      'entity_type_id' => 'banana',
      'config_entity_type' => TRUE,
    ]);

    $entity_reference_handler = new EntityReference();
    $entity_reference_handler->init('foo', $group_relation_type);
    $entity_reference_handler->configureField($base_field_definition);

    $this->assertEquals($group_relation_type->getEntityReferenceLabel(), $base_field_definition->getLabel());
    $this->assertEquals($group_relation_type->getEntityReferenceDescription(), $base_field_definition->getDescription());
    $this->assertEquals('group_config_wrapper', $base_field_definition->getSetting('target_type'));
    $this->assertEquals(['target_bundles' => [$group_relation_type->getEntityTypeId()]], $base_field_definition->getSetting('handler_settings'));
  }

}
