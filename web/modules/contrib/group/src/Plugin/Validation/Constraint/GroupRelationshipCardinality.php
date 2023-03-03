<?php

namespace Drupal\group\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks the cardinality limits for a relationship.
 *
 * Group relation plugins may limit the amount of times a single entity can be
 * added to a group as well as the amount of groups that single entity can be
 * added to. This constraint will enforce that behavior.
 *
 * @Constraint(
 *   id = "GroupRelationshipCardinality",
 *   label = @Translation("Relation cardinality check", context = "Validation"),
 *   type = "entity:group_relationship"
 * )
 */
class GroupRelationshipCardinality extends Constraint {

  /**
   * The message to show when an entity has reached the group cardinality.
   *
   * @var string
   */
  public $groupMessage = '@field: %content has reached the maximum amount of groups it can be added to';

  /**
   * The message to show when an entity has reached the entity cardinality.
   *
   * @var string
   */
  public $entityMessage = '@field: %content has reached the maximum amount of times it can be added to %group';

}
