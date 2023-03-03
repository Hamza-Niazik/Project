<?php

namespace Drupal\recurring_events\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a EventInstanceCreator annotation object.
 *
 * @Annotation
 */
class EventInstanceCreator extends Plugin {

  /**
   * Description of plugin
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
