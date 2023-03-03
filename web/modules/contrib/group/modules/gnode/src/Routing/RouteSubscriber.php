<?php

namespace Drupal\gnode\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Group Node routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.group_relationship.create_page')) {
      $copy = clone $route;
      $copy->setPath('group/{group}/node/create');
      $copy->setDefault('base_plugin_id', 'group_node');
      $collection->add('entity.group_relationship.group_node_create_page', $copy);
    }

    if ($route = $collection->get('entity.group_relationship.add_page')) {
      $copy = clone $route;
      $copy->setPath('group/{group}/node/add');
      $copy->setDefault('base_plugin_id', 'group_node');
      $collection->add('entity.group_relationship.group_node_add_page', $copy);
    }
  }

}
