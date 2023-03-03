<?php

namespace Drupal\group_test_plugin\Plugin\Group\Relation;

use Drupal\group\Plugin\Group\Relation\GroupRelationBase;

/**
 * Provides a group relation type for users.
 *
 * @GroupRelationType(
 *   id = "user_relation",
 *   label = @Translation("Group user"),
 *   description = @Translation("Relates users to groups without making them members."),
 *   entity_type_id = "user",
 *   pretty_path_key = "user",
 *   reference_label = @Translation("Username"),
 *   reference_description = @Translation("The name of the user you want to relate to the group"),
 *   admin_permission = "administer user_relation"
 * )
 */
class UserRelation extends GroupRelationBase {
}
