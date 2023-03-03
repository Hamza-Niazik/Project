<?php

/**
 * @file
 * Contains Views hooks.
 */

use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;

/**
 * Implements hook_views_data_alter().
 */
function group_views_data_alter(array &$data) {
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_types = $entity_type_manager->getDefinitions();

  // Get the data table for GroupRelationship entities.
  $data_table = $entity_types['group_relationship']->getDataTable();

  $plugin_manager = \Drupal::service('group_relation_type.manager');
  assert($plugin_manager instanceof GroupRelationTypeManagerInterface);

  // Add views data for all defined plugins so modules can provide default
  // views even though their plugins may not have been installed yet.
  foreach ($plugin_manager->getDefinitions() as $group_relation_type) {
    assert($group_relation_type instanceof GroupRelationTypeInterface);
    $entity_type_id = $group_relation_type->getEntityTypeId();
    if (!isset($entity_types[$entity_type_id])) {
      continue;
    }
    $entity_type = $entity_types[$entity_type_id];
    $entity_data_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();

    // We only add one 'group_relationship' entry per entity type.
    if (isset($data[$entity_data_table]['group_relationship'])) {
      continue;
    }

    $t_args = [
      '@entity_type' => $entity_type->getLabel(),
    ];

    // This relationship will allow a content entity to easily map to the group
    // content entity that ties it to a group, optionally filtering by plugin.
    $data[$entity_data_table]['group_relationship'] = [
      'title' => t('Group relationship for @entity_type', $t_args),
      'help' => t('Relates to the group relationship entities that represent the @entity_type.', $t_args),
      'relationship' => [
        'group' => t('Group relationship'),
        'base' => $data_table,
        'base field' => 'entity_id',
        'relationship field' => $entity_type->getKey('id'),
        'id' => 'group_relationship_to_entity_reverse',
        'label' => t('@entity_type group relationship', $t_args),
      ],
    ];
  }
}
