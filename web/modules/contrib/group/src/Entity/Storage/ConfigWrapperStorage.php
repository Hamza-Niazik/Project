<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for group config wrapper entities.
 */
class ConfigWrapperStorage extends SqlContentEntityStorage implements ConfigWrapperStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function supportsEntity(ConfigEntityInterface $entity) {
    return $this->supportsEntityTypeId($entity->getEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEntityTypeId($entity_type_id) {
    return array_key_exists($entity_type_id, $this->entityTypeBundleInfo->getBundleInfo($this->entityTypeId));
  }

  /**
   * {@inheritdoc}
   */
  public function wrapEntity(ConfigEntityInterface $entity, $create_if_missing = TRUE) {
    return $this->wrapEntityId($entity->getEntityTypeId(), $entity->id(), $create_if_missing);
  }

  /**
   * {@inheritdoc}
   */
  public function wrapEntityId($entity_type_id, $entity_id, $create_if_missing = TRUE) {
    if (!$this->supportsEntityTypeId($entity_type_id)) {
      throw new EntityStorageException(sprintf('Trying to wrap an unsupported entity of type "%s".', $entity_type_id));
    }

    $properties = [
      'bundle' => $entity_type_id,
      'entity_id' => $entity_id,
    ];

    if ($wrappers = $this->loadByProperties($properties)) {
      return reset($wrappers);
    }

    if ($create_if_missing) {
      $wrapper = $this->create($properties);
      $this->save($wrapper);
      return $wrapper;
    }

    return FALSE;
  }

}
