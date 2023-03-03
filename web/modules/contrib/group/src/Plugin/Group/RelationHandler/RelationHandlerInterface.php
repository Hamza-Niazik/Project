<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;

/**
 * Provides a common interface for group relation handlers.
 */
interface RelationHandlerInterface {

  /**
   * Initializes the handler.
   *
   * @param string $plugin_id
   *   The group relation type ID. Note: This is the actual plugin ID,
   *   including any potential derivative ID. To get the base plugin ID, you
   *   should use $group_relation_type->id().
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $group_relation_type
   *   The group relation type.
   */
  public function init($plugin_id, GroupRelationTypeInterface $group_relation_type);

}
