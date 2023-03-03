<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Defines an interface for relationship type entity storage classes.
 */
interface GroupRelationshipTypeStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Retrieves all relationship types for a group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to load the relationship types for.
   *
   * @return \Drupal\group\Entity\GroupRelationshipTypeInterface[]
   *   An array of relationship types indexed by their IDs.
   */
  public function loadByGroupType(GroupTypeInterface $group_type);

  /**
   * Retrieves relationship types by their responsible plugin ID.
   *
   * @param string|string[] $plugin_id
   *   The group relation type ID or an array of plugin IDs. If more than one
   *   plugin ID is provided, this will load all of the relationship types that
   *   match any of the provided plugin IDs.
   *
   * @return \Drupal\group\Entity\GroupRelationshipTypeInterface[]
   *   An array of relationship types indexed by their IDs.
   */
  public function loadByPluginId($plugin_id);

  /**
   * Retrieves relationship types which could serve a given entity type.
   *
   * @param string $entity_type_id
   *   An entity type ID which may be served by one or more relationship types.
   *
   * @return \Drupal\group\Entity\GroupRelationshipTypeInterface[]
   *   An array of relationship types indexed by their IDs.
   */
  public function loadByEntityTypeId($entity_type_id);

  /**
   * Creates a relationship type for a group type using a specific plugin.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to create the relationship type for.
   * @param string $plugin_id
   *   The group relation type ID to use.
   * @param array $configuration
   *   (optional) An array of group relation configuration.
   *
   * @return \Drupal\group\Entity\GroupRelationshipTypeInterface
   *   A new, unsaved GroupRelationshipType entity.
   */
  public function createFromPlugin(GroupTypeInterface $group_type, $plugin_id, array $configuration = []);

  /**
   * Constructs a relationship type ID based on a group type and plugin.
   *
   * @param string $group_type_id
   *   The group type ID.
   * @param string $plugin_id
   *   The ID of the plugin (to be) installed on the group type.
   *
   * @return string
   *   The relationship type ID.
   */
  public function getRelationshipTypeId($group_type_id, $plugin_id);

}
