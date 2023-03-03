<?php

namespace Drupal\group\Plugin\Group\Relation;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Provides a common interface for group relation type managers.
 */
interface GroupRelationTypeManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface {

  /**
   * Returns a handler instance for the given plugin and handler.
   *
   * Entity handlers are instantiated once per entity type and then cached
   * in the entity type manager, and so subsequent calls to getHandler() for
   * a particular entity type and handler type will return the same object.
   * This means that properties on a handler may be used as a static cache,
   * although as the handler is common to all entities of the same type,
   * any data that is per-entity should be keyed by the entity ID.
   *
   * @param string $plugin_id
   *   The plugin ID for this handler.
   * @param string $handler_type
   *   The handler type to create an instance for.
   *
   * @return object
   *   A handler instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getHandler($plugin_id, $handler_type);

  /**
   * Creates a new handler instance.
   *
   * Using ::getHandler() is preferred since that method has static caches.
   *
   * @param string $plugin_id
   *   The plugin ID for this handler.
   * @param string $handler_type
   *   The handler type to create an instance for.
   *
   * @return object
   *   A handler instance.
   *
   * @internal
   *   Marked as internal because the plugin definitions will become classes in
   *   a future release to further mimic the entity type system. Do not call
   *   this directly.
   */
  public function createHandlerInstance($plugin_id, $handler_type);

  /**
   * Creates a new access control handler instance.
   *
   * @param string $plugin_id
   *   The plugin ID for this access control handler.
   *
   * @return \Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface
   *   An access control handler instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the plugin doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the access control handler couldn't be loaded.
   */
  public function getAccessControlHandler($plugin_id);

  /**
   * Creates a new entity reference handler instance.
   *
   * @param string $plugin_id
   *   The plugin ID for this entity reference handler.
   *
   * @return \Drupal\group\Plugin\Group\RelationHandler\EntityReferenceInterface
   *   An entity reference handler instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the plugin doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the entity reference handler couldn't be loaded.
   */
  public function getEntityReferenceHandler($plugin_id);

  /**
   * Creates a new operation provider instance.
   *
   * @param string $plugin_id
   *   The plugin ID for this operation provider.
   *
   * @return \Drupal\group\Plugin\Group\RelationHandler\OperationProviderInterface
   *   An operation provider instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the plugin doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the operation provider couldn't be loaded.
   */
  public function getOperationProvider($plugin_id);

  /**
   * Creates a new permission provider instance.
   *
   * @param string $plugin_id
   *   The plugin ID for this permission provider.
   *
   * @return \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface
   *   A permission provider instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the plugin doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the permission provider couldn't be loaded.
   */
  public function getPermissionProvider($plugin_id);

  /**
   * Creates a new post install handler instance.
   *
   * @param string $plugin_id
   *   The plugin ID for this post install handler.
   *
   * @return \Drupal\group\Plugin\Group\RelationHandler\PostInstallInterface
   *   A post install handler instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the plugin doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the post install handler couldn't be loaded.
   */
  public function getPostInstallHandler($plugin_id);

  /**
   * Creates a new UI text provider instance.
   *
   * @param string $plugin_id
   *   The plugin ID for this UI text provider.
   *
   * @return \Drupal\group\Plugin\Group\RelationHandler\UiTextProviderInterface
   *   A UI text provider instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the plugin doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the UI text provider couldn't be loaded.
   */
  public function getUiTextProvider($plugin_id);

  /**
   * Returns a plugin collection of all installed group relations.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to retrieve installed plugins for.
   *
   * @return \Drupal\group\Plugin\Group\Relation\GroupRelationCollection
   *   A plugin collection with an instance of every installed plugin.
   */
  public function getInstalled(GroupTypeInterface $group_type);

  /**
   * Returns the plugin ID of all group relations in use.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to retrieve plugin IDs for.
   *
   * @return string[]
   *   A list of all installed group relation type IDs for the given group type.
   */
  public function getInstalledIds(GroupTypeInterface $group_type);

  /**
   * Returns the ID of all plugins that deal with a given entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string[]
   *   The plugin IDs.
   */
  public function getPluginIdsByEntityTypeId($entity_type_id);

  /**
   * Returns the ID of all plugins that define access for a given entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string[]
   *   The plugin IDs.
   */
  public function getPluginIdsByEntityTypeAccess($entity_type_id);

  /**
   * Installs all plugins which are marked as enforced.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   (optional) The group type to install enforced plugins on. Leave blank to
   *   run the installation process for all group types.
   */
  public function installEnforced(GroupTypeInterface $group_type = NULL);

  /**
   * Retrieves all of the relationship type IDs for a content plugin.
   *
   * @param string $plugin_id
   *   The ID of the plugin to retrieve relationship type IDs for.
   *
   * @return string[]
   *   An array of relationship type IDs.
   */
  public function getRelationshipTypeIds($plugin_id);

  /**
   * Retrieves a list of relationship type IDs per plugin ID.
   *
   * @return array
   *   An array of relationship type ID arrays, keyed by plugin ID.
   */
  public function getPluginGroupRelationshipTypeMap();

  /**
   * Retrieves a list of plugin IDs per group type ID.
   *
   * @return array
   *   An array of content plugin ID arrays, keyed by group type ID.
   */
  public function getGroupTypePluginMap();

  /**
   * Clears the static per group type plugin collection cache.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   (optional) The group type to clear the cache for. Leave blank to clear
   *   the cache for all group types.
   */
  public function clearCachedGroupTypeCollections(GroupTypeInterface $group_type = NULL);

  /**
   * Clears static and persistent plugin ID map caches.
   */
  public function clearCachedPluginMaps();

}
