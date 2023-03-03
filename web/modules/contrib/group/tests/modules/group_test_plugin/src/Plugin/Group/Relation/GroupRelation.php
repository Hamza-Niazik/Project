<?php

namespace Drupal\group_test_plugin\Plugin\Group\Relation;

use Drupal\group\Plugin\Group\Relation\GroupRelationBase;

/**
 * Provides a group relation type for groups.
 *
 * @GroupRelationType(
 *   id = "group_relation",
 *   label = @Translation("Subgroup"),
 *   description = @Translation("Relates groups to groups as subgroups."),
 *   entity_type_id = "group",
 *   entity_bundle = "default",
 *   pretty_path_key = "subgroup",
 *   reference_label = @Translation("Group name"),
 *   reference_description = @Translation("The name of the group you want to relate to the group")
 * )
 */
class GroupRelation extends GroupRelationBase {
}
