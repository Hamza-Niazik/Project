<?php

namespace Drupal\recurring_events_registration\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * The ComputedRegistrantTitleFieldItemList class.
 */
final class ComputedRegistrantTitleFieldItemList extends FieldItemList {
  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $registrant = $this->getEntity();
    $config = \Drupal::config('recurring_events_registration.registrant.config');
    $title_config = $config->get('title');

    $data = [
      'registrant' => $registrant,
    ];
    $title = \Drupal::service('token')->replace($title_config, $data);
    $this->list[0] = $this->createItem(0, $title);
  }

}
