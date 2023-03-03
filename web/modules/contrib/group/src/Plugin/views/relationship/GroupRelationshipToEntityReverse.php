<?php

namespace Drupal\group\Plugin\views\relationship;

/**
 * A relationship handler which reverses group relationship entity references.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("group_relationship_to_entity_reverse")
 */
class GroupRelationshipToEntityReverse extends GroupRelationshipToEntityBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetEntityType() {
    return $this->definition['entity_type'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getJoinFieldType() {
    return 'field';
  }

}
