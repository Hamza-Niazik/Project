<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines an interface for group config wrapper entity storage classes.
 */
interface ConfigWrapperStorageInterface extends ContentEntityStorageInterface {

  /**
   * Checks whether the config entity can be wrapped.
   *
   * This will check if any bundle exists for config wrappers that would support
   * adding the provided config entity to a group.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The config entity to check.
   *
   * @return bool
   *   Whether wrapping is supported.
   */
  public function supportsEntity(ConfigEntityInterface $entity);

  /**
   * Checks whether the config entity type ID can be wrapped.
   *
   * This will check if any bundle exists for config wrappers that would support
   * adding config entities of the provided entity type ID to a group.
   *
   * @param string $entity_type_id
   *   The config entity type ID to check.
   *
   * @return bool
   *   Whether wrapping is supported.
   */
  public function supportsEntityTypeId($entity_type_id);

  /**
   * Retrieves a ConfigWrapper entity for a given config entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The config entity to retrieve a wrapper for.
   * @param bool $create_if_missing
   *   (optional) Whether to create a wrapper if none exists yet. Defaults to
   *   TRUE.
   *
   * @return \Drupal\group\Entity\ConfigWrapperInterface|false
   *   A new or loaded ConfigWrapper entity or FALSE if $create_if_missing was
   *   set to FALSE and no wrapper could be loaded.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when an unsupported entity is passed.
   */
  public function wrapEntity(ConfigEntityInterface $entity, $create_if_missing = TRUE);

  /**
   * Retrieves a ConfigWrapper entity for a given config entity ID.
   *
   * @param string $entity_type_id
   *   The config entity type ID to retrieve a wrapper for.
   * @param string $entity_id
   *   The config entity ID to retrieve a wrapper for.
   * @param bool $create_if_missing
   *   (optional) Whether to create a wrapper if none exists yet. Defaults to
   *   TRUE.
   *
   * @return \Drupal\group\Entity\ConfigWrapperInterface|false
   *   A new or loaded ConfigWrapper entity or FALSE if $create_if_missing was
   *   set to FALSE and no wrapper could be loaded.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when an unsupported entity type ID is passed.
   */
  public function wrapEntityId($entity_type_id, $entity_id, $create_if_missing = TRUE);

}
