<?php

namespace Drupal\recurring_events\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Event series type entities.
 */
interface EventSeriesTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this eventseries type.
   */
  public function getDescription();

}
