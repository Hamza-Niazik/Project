<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the storage handler class for relationship entities.
 *
 * This extends the base storage class, adding required special handling for
 * loading relationship entities based on group and plugin information.
 */
class GroupRelationshipStorage extends SqlContentEntityStorage implements GroupRelationshipStorageInterface {

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $pluginManager;

  /**
   * Static cache for looking up relationship entities for groups.
   *
   * @var array
   */
  protected $loadByGroupCache = [];

  /**
   * Static cache for looking up relationship entities for entities.
   *
   * @var array
   */
  protected $loadByEntityCache = [];

  /**
   * Static cache for looking up relationship entities for plugins.
   *
   * @var array
   */
  protected $loadByPluginCache = [];

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->pluginManager = $container->get('group_relation_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function restore(EntityInterface $entity) {
    assert($entity instanceof GroupRelationshipInterface);
    // It seems that SqlFieldableEntityTypeListenerTrait::copyData() does not
    // know how to get the denormalized data. Let's make sure it's always there.
    if (!$entity->getPluginId()) {
      $entity->set('plugin_id', $entity->getRelationshipType()->getPluginId());
    }
    if (!$entity->getGroupTypeId()) {
      $entity->set('group_type', $entity->getRelationshipType()->getGroupTypeId());
    }
    parent::restore($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function createForEntityInGroup(EntityInterface $entity, GroupInterface $group, $plugin_id, $values = []) {
    // An unsaved entity cannot have any relationships.
    if ($entity->id() === NULL) {
      throw new EntityStorageException("Cannot add an unsaved entity to a group.");
    }

    // An unsaved group cannot have any relationships.
    if ($group->id() === NULL) {
      throw new EntityStorageException("Cannot add an entity to an unsaved group.");
    }

    // Check whether the entity can actually be added to the group.
    $group_relation_type = $group->getGroupType()->getPlugin($plugin_id)->getRelationType();
    if ($entity->getEntityTypeId() != $group_relation_type->getEntityTypeId()) {
      throw new EntityStorageException("Invalid plugin provided for adding the entity to the group.");
    }

    // Verify the bundle as well if the plugin is specific about them.
    $supported_bundle = $group_relation_type->getEntityBundle();
    if ($supported_bundle !== FALSE) {
      if ($entity->bundle() != $supported_bundle) {
        throw new EntityStorageException("The provided plugin provided does not support the entity's bundle.");
      }
    }

    // If the entity is config, we need to use the wrapper for it.
    if ($group_relation_type->handlesConfigEntityType()) {
      $storage = $this->entityTypeManager->getStorage('group_config_wrapper');
      assert($storage instanceof ConfigWrapperStorageInterface);
      $entity = $storage->wrapEntity($entity);
    }

    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $relationship_type_id = $storage->getRelationshipTypeId($group->bundle(), $plugin_id);

    // Set the necessary keys for a valid GroupRelationship entity.
    $keys = [
      'type' => $relationship_type_id,
      'gid' => $group->id(),
      'entity_id' => $entity->id(),
    ];

    // Return an unsaved GroupRelationship entity.
    return $this->create($keys + $values);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByGroup(GroupInterface $group, $plugin_id = NULL) {
    // An unsaved group cannot have any content.
    $group_id = $group->id();
    if ($group_id === NULL) {
      return [];
    }

    $cache_key = $plugin_id ?: '---ALL---';
    if (!isset($this->loadByGroupCache[$group_id][$cache_key])) {
      $query = $this->database
        ->select($this->dataTable, 'd')
        ->fields('d', ['id'])
        ->condition('gid', $group_id);

      if ($plugin_id) {
        $query->condition('plugin_id', $plugin_id);
      }

      $this->loadByGroupCache[$group_id][$cache_key] = $query->execute()->fetchCol();
    }

    if (!empty($this->loadByGroupCache[$group_id][$cache_key])) {
      return $this->loadMultiple($this->loadByGroupCache[$group_id][$cache_key]);
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadByEntity(EntityInterface $entity, $plugin_id = NULL) {
    // An unsaved entity cannot have any relationships.
    $entity_id = $entity->id();
    if ($entity_id === NULL) {
      return [];
    }

    $entity_type_id = $entity->getEntityTypeId();
    $cache_key = '---ALL---';
    if (isset($plugin_id)) {
      $cache_key = $plugin_id;

      if ($entity_type_id !== $this->pluginManager->getDefinition($plugin_id)->getEntityTypeId()) {
        throw new EntityStorageException(sprintf('Loading relationships for the given entity of type "%s" not supported by the provided plugin "%s".', $entity_type_id, $plugin_id));
      }
    }

    if (!isset($this->loadByEntityCache[$entity_type_id][$entity_id][$cache_key])) {
      $plugin_ids = $plugin_id ? [$plugin_id] : $this->pluginManager->getPluginIdsByEntityTypeId($entity_type_id);

      $result = [];
      if (!empty($plugin_ids)) {
        // If the entity is config, we need to use the wrapper for it.
        if ($entity instanceof ConfigEntityInterface) {
          $storage = $this->entityTypeManager->getStorage('group_config_wrapper');
          assert($storage instanceof ConfigWrapperStorageInterface);
          $entity_id = $storage->wrapEntity($entity)->id();
        }

        $result = $this->database
          ->select($this->dataTable, 'd')
          ->fields('d', ['id'])
          ->condition('entity_id', $entity_id)
          ->condition('plugin_id', $plugin_ids, 'IN')
          ->execute()
          ->fetchCol();
      }

      $this->loadByEntityCache[$entity_type_id][$entity_id][$cache_key] = $result;
    }

    if (!empty($this->loadByEntityCache[$entity_type_id][$entity_id][$cache_key])) {
      return $this->loadMultiple($this->loadByEntityCache[$entity_type_id][$entity_id][$cache_key]);
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadByPluginId($plugin_id) {
    if (!isset($this->loadByPluginCache[$plugin_id])) {
      $query = $this->database
        ->select($this->dataTable, 'd')
        ->fields('d', ['id'])
        ->condition('plugin_id', $plugin_id);

      $this->loadByPluginCache[$plugin_id] = $query->execute()->fetchCol();
    }

    if (!empty($this->loadByPluginCache[$plugin_id])) {
      return $this->loadMultiple($this->loadByPluginCache[$plugin_id]);
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    parent::resetCache($ids);
    $this->loadByGroupCache = [];
    $this->loadByEntityCache = [];
    $this->loadByPluginCache = [];
  }

}
