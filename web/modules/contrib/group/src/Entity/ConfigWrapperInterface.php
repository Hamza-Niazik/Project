<?php

namespace Drupal\group\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a ConfigWrapper entity.
 *
 * @ingroup group
 */
interface ConfigWrapperInterface extends ContentEntityInterface {

  /**
   * Returns the wrapped config entity.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The wrapped config entity.
   */
  public function getConfigEntity();

  /**
   * Returns the wrapped config entity ID.
   *
   * @return string
   *   The wrapped config entity ID.
   */
  public function getConfigEntityId();

}
