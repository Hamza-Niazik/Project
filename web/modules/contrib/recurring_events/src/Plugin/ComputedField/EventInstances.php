<?php

namespace Drupal\recurring_events\Plugin\ComputedField;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

class EventInstances extends EntityReferenceFieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritDoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    if (!empty($entity->id())) {
      $event_instances = \Drupal::entityTypeManager()->getStorage('eventinstance')->loadByProperties([
        'eventseries_id' => $entity->id(),
      ]);

      foreach ($event_instances as $key => $instance) {
        $this->list[$key] = $this->createItem($key, $instance);
      }
    }
  }
}
