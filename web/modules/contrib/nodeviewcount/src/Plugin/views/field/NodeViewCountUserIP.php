<?php

namespace Drupal\nodeviewcount\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * NodeViewCountUserIP class.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_view_count_user_ip")
 */
class NodeViewCountUserIP extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return [
      '#markup' => $values->nodeviewcount_uip,
    ];
  }

}
