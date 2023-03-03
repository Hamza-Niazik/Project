<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the config wrapper schema handler.
 */
class ConfigWrapperStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    if ($base_table = $this->storage->getBaseTable()) {
      // We only ever want one wrapper per config entity. This enforces it while
      // also creating an index behind the scenes for faster lookups.
      $schema[$base_table]['unique keys'] += [
        $this->getEntityIndexName($entity_type, 'load_by_config') => ['bundle', 'entity_id'],
      ];
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);

    $field_name = $storage_definition->getName();
    if ($field_name === 'entity_id') {
      $schema['fields'][$field_name]['not null'] = TRUE;
    }

    return $schema;
  }

}
