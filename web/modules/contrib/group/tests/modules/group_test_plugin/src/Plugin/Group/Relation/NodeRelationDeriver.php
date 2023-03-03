<?php

namespace Drupal\group_test_plugin\Plugin\Group\Relation;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;

class NodeRelationDeriver extends DeriverBase {

  /**
   * {@inheritdoc}.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    assert($base_plugin_definition instanceof GroupRelationTypeInterface);
    $this->derivatives = [];

    $this->derivatives['page'] = clone $base_plugin_definition;
    $this->derivatives['page']->set('entity_bundle', 'page');
    $this->derivatives['page']->set('label', t('Page relations (generic)'));
    $this->derivatives['page']->set('description', t('Relates pages to groups.'));
    $this->derivatives['page']->set('admin_permission', 'administer node_relation:page');

    $this->derivatives['article'] = clone $base_plugin_definition;
    $this->derivatives['article']->set('entity_bundle', 'article');
    $this->derivatives['article']->set('label', t('Article relations (generic)'));
    $this->derivatives['article']->set('description', t('Relates articles to groups.'));
    $this->derivatives['article']->set('admin_permission', 'administer node_relation:article');

    return $this->derivatives;
  }

}
