<?php

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Provides an interface defining a group type entity.
 */
interface GroupTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface, RevisionableEntityBundleInterface {

  /**
   * The maximum length of the ID, in characters.
   *
   * This is shorter than the default limit of 32 to allow group roles to have
   * an ID which can be appended to the group type's ID without exceeding the
   * default limit there. We leave of 10 characters to account for '-anonymous'.
   */
  const ID_MAX_LENGTH = 22;

  /**
   * Gets the group roles.
   *
   * @param bool $include_synchronized
   *   (optional) Whether to include roles that synchronize to a global role in
   *   the result. Defaults to TRUE.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]
   *   The group roles this group type uses.
   */
  public function getRoles($include_synchronized = TRUE);

  /**
   * Gets the role IDs.
   *
   * @param bool $include_synchronized
   *   (optional) Whether to include roles that synchronize to a global role in
   *   the result. Defaults to TRUE.
   *
   * @return string[]
   *   The ids of the group roles this group type uses.
   */
  public function getRoleIds($include_synchronized = TRUE);

  /**
   * Sets whether a new revision should be created by default.
   *
   * @param bool $new_revision
   *   TRUE if a new revision should be created by default.
   */
  public function setNewRevision($new_revision);

  /**
   * Returns whether the group creator automatically receives a membership.
   *
   * @return bool
   *   Whether the group creator automatically receives a membership.
   */
  public function creatorGetsMembership();

  /**
   * Returns whether the group creator must complete their membership.
   *
   * @return bool
   *   Whether the group creator must complete their membership.
   */
  public function creatorMustCompleteMembership();

  /**
   * Gets the IDs of the group roles a group creator should receive.
   *
   * @return string[]
   *   The IDs of the group role the group creator should receive.
   */
  public function getCreatorRoleIds();

  /**
   * Returns the installed group relations for this group type.
   *
   * @return \Drupal\group\Plugin\Group\Relation\GroupRelationCollection
   *   The group relation collection.
   */
  public function getInstalledPlugins();

  /**
   * Checks whether a group relation is installed for this group type.
   *
   * @param string $plugin_id
   *   The group relation type ID to check for.
   *
   * @return bool
   *   Whether the group relation is installed.
   */
  public function hasPlugin($plugin_id);

  /**
   * Gets an installed group relation for this group type.
   *
   * Warning: In places where the plugin may not be installed on the group type,
   * you should always run ::hasPlugin() first or you may risk ending up with
   * crashes or unreliable data.
   *
   * @param string $plugin_id
   *   The group relation type ID.
   *
   * @return \Drupal\group\Plugin\Group\Relation\GroupRelationInterface
   *   The installed group relation for the group type.
   */
  public function getPlugin($plugin_id);

}
