<?php

namespace Drupal\group_test_plugin\Plugin\Group\Relation;

use Drupal\group\Plugin\Group\Relation\GroupRelationBase;

/**
 * Provides a group relation type for node types.
 *
 * @GroupRelationType(
 *   id = "node_type_relation",
 *   label = @Translation("Node type relation (generic)"),
 *   description = @Translation("Relates node types to groups."),
 *   entity_type_id = "node_type",
 *   entity_access = TRUE,
 *   admin_permission = "administer node_type_relation",
 * )
 */
class NodeTypeRelation extends GroupRelationBase {
}
