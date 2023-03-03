<?php

namespace Drupal\recurring_events;

use Drupal\recurring_events\Entity\EventSeries;

/**
 * An interface for all EventInstanceCreator type plugins.
 */
interface EventInstanceCreatorInterface {

  /**
   * Provide a description of the plugin.
   *
   * @return string
   *   A string description of the plugin.
   */
  public function description();

  /**
   * Process the instances for a particular series.
   *
   * @param EventSeries $series
   *   The series for which to process instances.
   */
  public function processInstances(EventSeries $series);
}
