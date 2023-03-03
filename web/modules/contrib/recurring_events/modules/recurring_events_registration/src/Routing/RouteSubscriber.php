<?php

namespace Drupal\recurring_events_registration\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change path '/user/login' to '/login'.
    if ($route = $collection->get('entity.registrant.latest_version')) {
      $route->setRequirement('eventinstance', '\d+');
      $option = $route->getOption('parameters');
      $option['eventinstance'] = [
        'type' => 'entity:eventinstance',
        'load_latest_revision' => TRUE,
      ];
      $route->setOption('parameters', $option);
    }
  }

}
