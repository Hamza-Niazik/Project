<?php

namespace Drupal\group_recurring_events_series\Routing;

use Drupal\recurring_events\Entity\EventSeriesType;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for group_recurring_events_series group content.
 */
class GroupEventSeriesRouteProvider {

  /**
   * Provides the shared collection route for group event series plugins.
   */
  public function getRoutes() {
    $routes = $plugin_ids = $permissions_add = $permissions_create = [];

    foreach (EventSeriesType::loadMultiple() as $name => $eventseries_type) {
      $plugin_id = "group_recurring_events_series:$name";

      $plugin_ids[] = $plugin_id;
      $permissions_add[] = "create $plugin_id content";
      $permissions_create[] = "create $plugin_id entity";
    }

    // If there are no event series types yet, we cannot have any plugin IDs
    // and should therefore exit early because we cannot have any routes for
    // them either.
    if (empty($plugin_ids)) {
      return $routes;
    }

    $routes['entity.group_content.group_recurring_events_series_relate_page'] = new Route('group/{group}/eventseries/add');
    $routes['entity.group_content.group_recurring_events_series_relate_page']
      ->setDefaults([
        '_title' => 'Add existing content',
        '_controller' => '\Drupal\group_recurring_events_series\Controller\GroupEventSeriesController::addPage',
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_add))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    $routes['entity.group_content.group_recurring_events_series_add_page'] = new Route('group/{group}/eventseries/create');
    $routes['entity.group_content.group_recurring_events_series_add_page']
      ->setDefaults([
        '_title' => 'Add new content',
        '_controller' => '\Drupal\group_recurring_events_series\Controller\GroupEventSeriesController::addPage',
        'create_mode' => TRUE,
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_create))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    return $routes;
  }

}
