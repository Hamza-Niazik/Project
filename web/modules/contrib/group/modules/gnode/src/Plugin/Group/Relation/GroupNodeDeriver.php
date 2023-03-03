<?php

namespace Drupal\gnode\Plugin\Group\Relation;

use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
use Drupal\node\Entity\NodeType;
use Drupal\Component\Plugin\Derivative\DeriverBase;

class GroupNodeDeriver extends DeriverBase {

  /**
   * {@inheritdoc}.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    assert($base_plugin_definition instanceof GroupRelationTypeInterface);
    $this->derivatives = [];

    foreach (NodeType::loadMultiple() as $name => $node_type) {
      $label = $node_type->label();

      $this->derivatives[$name] = clone $base_plugin_definition;
      $this->derivatives[$name]->set('entity_bundle', $name);
      $this->derivatives[$name]->set('label', t('Group node (@type)', ['@type' => $label]));
      $this->derivatives[$name]->set('description', t('Adds %type content to groups both publicly and privately.', ['%type' => $label]));
    }

    return $this->derivatives;
  }

}
