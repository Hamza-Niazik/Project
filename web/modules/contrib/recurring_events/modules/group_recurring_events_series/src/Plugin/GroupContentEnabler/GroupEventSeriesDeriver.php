<?php

namespace Drupal\group_recurring_events_series\Plugin\GroupContentEnabler;

use Drupal\recurring_events\Entity\EventSeriesType;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a deriver for group event series entities.
 */
class GroupEventSeriesDeriver extends DeriverBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach (EventSeriesType::loadMultiple() as $name => $eventseries_type) {
      $label = $eventseries_type->label();

      $this->derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => $this->t('Group event series (@type)', ['@type' => $label]),
        'description' => $this->t('Adds %type content to groups both publicly and privately.', ['%type' => $label]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
