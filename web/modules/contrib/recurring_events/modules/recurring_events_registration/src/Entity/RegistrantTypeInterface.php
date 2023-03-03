<?php

namespace Drupal\recurring_events_registration\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Registrant type entities.
 */
interface RegistrantTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this Registrant type.
   */
  public function getDescription();

}
