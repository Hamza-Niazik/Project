<?php

namespace Drupal\recurring_events\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Event instance type entities.
 */
interface EventInstanceTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this eventinstance type.
   */
  public function getDescription();

}
