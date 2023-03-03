<?php

namespace Drupal\group\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Group relationship entity.
 *
 * @ingroup group
 */
interface GroupRelationshipInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Returns the relationship type entity the relationship uses.
   *
   * @return \Drupal\group\Entity\GroupRelationshipTypeInterface
   *   The relationship type entity the relationship uses.
   */
  public function getRelationshipType();

  /**
   * Returns the group the relationship belongs to.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The group the relationship belongs to.
   */
  public function getGroup();

  /**
   * Returns the group ID the relationship belongs to.
   *
   * @return int
   *   The group ID the relationship belongs to.
   */
  public function getGroupId();

  /**
   * Returns the group type the relationship belongs to.
   *
   * @return \Drupal\group\Entity\GroupTypeInterface
   *   The group type the relationship belongs to.
   */
  public function getGroupType();

  /**
   * Returns the group type ID the relationship belongs to.
   *
   * @return string
   *   The group type ID the relationship belongs to.
   */
  public function getGroupTypeId();

  /**
   * Returns the entity that was added as a relationship.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *  The entity that was added as a relationship.
   */
  public function getEntity();

  /**
   * Returns the ID of the related entity.
   *
   * @return int|string
   *   The ID of the related entity.
   */
  public function getEntityId();

  /**
   * Returns the group relation that handles the relationship.
   *
   * @return \Drupal\group\Plugin\Group\Relation\GroupRelationInterface
   *   The group relation that handles the relationship.
   */
  public function getPlugin();

  /**
   * Gets the group relation type ID the relationship uses.
   *
   * @return string
   *   The group relation type ID the relationship uses.
   */
  public function getPluginId();

  /**
   * Loads relationship entities by their responsible plugin ID.
   *
   * @param string $plugin_id
   *   The group relation type ID.
   *
   * @return \Drupal\group\Entity\GroupRelationshipInterface[]
   *   An array of relationship entities indexed by their IDs.
   */
  public static function loadByPluginId($plugin_id);

  /**
   * Loads relationship entities which reference a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity which may be within one or more groups.
   *
   * @return \Drupal\group\Entity\GroupRelationshipInterface[]
   *   An array of relationship entities which reference the given entity.
   */
  public static function loadByEntity(EntityInterface $entity);

}
