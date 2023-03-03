<?php

namespace Drupal\group\Entity\Views;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
use Drupal\views\EntityViewsData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the views data for the relationship entity type.
 */
class GroupRelationshipViewsData extends EntityViewsData {

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $views_data = parent::createInstance($container, $entity_type);
    $views_data->pluginManager = $container->get('group_relation_type.manager');
    return $views_data;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Get the data table for GroupRelationship entities.
    $data_table = $this->entityType->getDataTable();

    // Add a custom numeric argument for the parent group ID that allows us to
    // use replacement titles with the parent group's label.
    $data[$data_table]['gid']['argument'] = [
      'id' => 'group_id',
      'numeric' => TRUE,
    ];

    // Unset the 'entity_id' field relationship as we want a more powerful one.
    // @todo Eventually, we may want to replace all of 'entity_id'.
    unset($data[$data_table]['entity_id']['relationship']);

    $entity_types = $this->entityTypeManager->getDefinitions();

    // Add views data for all defined plugins so modules can provide default
    // views even though their plugins may not have been installed yet.
    foreach ($this->pluginManager->getDefinitions() as $group_relation_type) {
      assert($group_relation_type instanceof GroupRelationTypeInterface);
      $entity_type_id = $group_relation_type->getEntityTypeId();
      if (!isset($entity_types[$entity_type_id])) {
        continue;
      }
      $entity_type = $entity_types[$entity_type_id];
      assert($entity_type instanceof EntityTypeInterface);
      $entity_data_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();

      // Create a unique field name for this views field.
      $field_name = 'gc__' . $entity_type_id;

      // We only add one 'group_relationship' relationship per entity type.
      if (isset($data[$entity_data_table][$field_name])) {
        continue;
      }

      $t_args = [
        '@entity_type' => $entity_type->getLabel(),
      ];

      // This relationship will allow a relationship entity to easily map to a
      // content entity that it ties to a group, optionally filtering by plugin.
      $data[$data_table][$field_name] = [
        'title' => $this->t('@entity_type from group relationship', $t_args),
        'help' => $this->t('Relates to the @entity_type entity the group relationship represents.', $t_args),
        'relationship' => [
          'group' => $entity_type->getLabel(),
          'base' => $entity_data_table,
          'base field' => $entity_type->getKey('id'),
          'relationship field' => 'entity_id',
          'id' => 'group_relationship_to_entity',
          'label' => $this->t('Group relationship @entity_type', $t_args),
          'target_entity_type' => $entity_type_id,
        ],
      ];
    }

    return $data;
  }

}
