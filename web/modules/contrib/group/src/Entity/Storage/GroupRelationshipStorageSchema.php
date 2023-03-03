<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the relationship schema handler.
 */
class GroupRelationshipStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    if ($data_table = $this->storage->getDataTable()) {
      $schema[$data_table]['indexes'] += [
        $this->getEntityIndexName($entity_type, 'load_by_group') => ['gid', 'plugin_id', 'entity_id'],
        $this->getEntityIndexName($entity_type, 'load_by_entity') => ['entity_id', 'plugin_id'],
        $this->getEntityIndexName($entity_type, 'load_by_plugin') => ['plugin_id'],
        $this->getEntityIndexName($entity_type, 'sync_scope_checks') => ['group_type', 'plugin_id'],
      ];
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);

    if ($this->storage->getDataTable()) {
      $field_name = $storage_definition->getName();

      switch ($field_name) {
        case 'plugin_id':
          // The default field size would be 255, which is far too long. We can
          // reasonably assume that the total length of a plugin ID and perhaps
          // derivative ID would not exceed 64 characters. If we ever get a
          // complaint about this, we can bump it up to 128, but for now let's
          // choose performance over edge cases.
          $schema['fields'][$field_name]['length'] = 64;

        // Deliberate break missing above because plugin_id also needs this.
        case 'gid':
        case 'entity_id':
          // Improves the performance of the indexes defined above.
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;
      }
    }

    return $schema;
  }

}
