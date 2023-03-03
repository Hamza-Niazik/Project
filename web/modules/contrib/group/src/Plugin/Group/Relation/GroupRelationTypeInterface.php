<?php

namespace Drupal\group\Plugin\Group\Relation;

use Drupal\Component\Plugin\Definition\DerivablePluginDefinitionInterface;
use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;

/**
 * Provides an interface for a group relation type and its metadata.
 *
 * Group relation type classes can provide docblock annotations. The group
 * relation type manager will use these annotations to populate the group
 * relation type object with properties.
 *
 * @see \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
 * @see hook_group_relation_type_alter()
 */
interface GroupRelationTypeInterface extends PluginDefinitionInterface, DerivablePluginDefinitionInterface {

  /**
   * Gets any arbitrary property.
   *
   * @param string $property
   *   The property to retrieve.
   *
   * @return mixed
   *   The value for that property, or NULL if the property does not exist.
   */
  public function get($property);

  /**
   * Sets a value to an arbitrary property.
   *
   * @param string $property
   *   The property to use for the value.
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function set($property, $value);

  /**
   * Returns the administrative label for the plugin.
   *
   * @return \Drupal\Core\Annotation\Translation
   *   The plugin label.
   */
  public function getLabel();

  /**
   * Returns the administrative description for the plugin.
   *
   * @return \Drupal\Core\Annotation\Translation
   *   The plugin description.
   */
  public function getDescription();

  /**
   * Returns the label for the entity reference field.
   *
   * This allows you to specify the label for the entity reference field
   * pointing to the entity that is to be added as a relationship.
   *
   * @return \Drupal\Core\Annotation\Translation|null
   *   The label for the entity reference field or NULL if none was set.
   */
  public function getEntityReferenceLabel();

  /**
   * Returns the description for the entity reference field.
   *
   * This allows you to specify the description for the entity reference field
   * pointing to the entity that is to be added as a relationship.
   *
   * @return \Drupal\Core\Annotation\Translation|null
   *   The description for the entity reference field or NULL if none was set.
   */
  public function getEntityReferenceDescription();

  /**
   * Returns the entity type ID the plugin supports.
   *
   * @return string
   *   The entity type ID.
   */
  public function getEntityTypeId();

  /**
   * Returns the entity bundle the plugin supports.
   *
   * @return string|false
   *   The bundle name or FALSE in case it supports all bundles.
   */
  public function getEntityBundle();

  /**
   * Returns whether this plugin deals with a config entity type.
   *
   * @return bool
   *   Whether this plugin deals with a config entity type.
   */
  public function handlesConfigEntityType();

  /**
   * Returns whether this plugin defines entity access.
   *
   * @return bool
   *   Whether this plugin defines entity access.
   *
   * @see \Drupal\group\Annotation\GroupRelationType::$entity_access
   */
  public function definesEntityAccess();

  /**
   * Returns whether this plugin is always on.
   *
   * @return bool
   *   The 'enforced' status.
   */
  public function isEnforced();

  /**
   * Returns whether this plugin can only be (un)installed through code.
   *
   * @return bool
   *   The 'code_only' status.
   */
  public function isCodeOnly();

  /**
   * Returns the pretty path key for use in path aliases.
   *
   * @return string
   *   The plugin-provided pretty path key, defaults to 'content'.
   */
  public function getPrettyPathKey();

  /**
   * Gets the name of the admin permission.
   *
   * @return string|false
   *   The admin permission name or FALSE if none was set.
   */
  public function getAdminPermission();

}
