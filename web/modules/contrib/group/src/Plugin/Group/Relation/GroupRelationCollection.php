<?php

namespace Drupal\group\Plugin\Group\Relation;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of group relations.
 */
class GroupRelationCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\group\Plugin\Group\Relation\GroupRelationInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   *
   * Sorts plugins by provider.
   */
  public function sortHelper($aID, $bID) {
    $a_provider = $this->get($aID)->getRelationType()->getProvider();
    $b_provider = $this->get($bID)->getRelationType()->getProvider();

    if ($a_provider != $b_provider) {
      return strnatcasecmp($a_provider, $b_provider);
    }

    return parent::sortHelper($aID, $bID);
  }

}
