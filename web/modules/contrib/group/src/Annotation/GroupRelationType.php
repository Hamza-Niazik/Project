<?php

namespace Drupal\group\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\group\Plugin\Group\Relation\GroupRelationType as GroupRelationTypeDefinition;

/**
 * Defines a GroupRelationType annotation object.
 *
 * Plugin Namespace: Plugin\Group\Relation
 *
 * For a working example, see
 * \Drupal\group\Plugin\Group\Relation\GroupMembership
 *
 * @see \Drupal\group\Plugin\Group\Relation\GroupRelationInterface
 * @see \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class GroupRelationType extends Plugin {

  /**
   * {@inheritdoc}
   */
  public function get() {
    return new GroupRelationTypeDefinition($this->definition);
  }

}
