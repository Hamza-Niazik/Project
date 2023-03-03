<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Provides a common interface for group relation entity reference handlers.
 */
interface EntityReferenceInterface extends RelationHandlerInterface {

  /**
   * Sets up the entity reference field on the GroupRelationship entity.
   *
   * When you add content to a group using a plugin, the entity reference field
   * that allows you to select the content you wish to add may need specific
   * settings added to it. This is where you can do all of that.
   *
   * @param \Drupal\Core\Field\BaseFieldDefinition $entity_reference
   *   The entity reference field for the plugin.
   */
  public function configureField(BaseFieldDefinition $entity_reference);

}
