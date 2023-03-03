<?php

namespace Drupal\recurring_events\Plugin\EventInstanceCreator;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\recurring_events\Entity\EventSeries;
use Drupal\recurring_events\EventInstanceCreatorBase;

/**
 * Plugin that removes and recreates all event instances.
 *
 * @EventInstanceCreator(
 *   id = "recurring_events_eventinstance_recreator",
 *   description = @Translation("Recreate Event Instances")
 * )
 */
class RecreateEventInstanceCreator extends EventInstanceCreatorBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritDoc}
   */
  public function processInstances(EventSeries $series) {
    $this->creationService->clearEventInstances($series);
    $this->creationService->createInstances($series);
  }

}
