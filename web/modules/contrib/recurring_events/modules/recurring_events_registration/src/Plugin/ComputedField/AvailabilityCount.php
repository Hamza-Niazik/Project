<?php

namespace Drupal\recurring_events_registration\Plugin\ComputedField;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\recurring_events_registration\Traits\RegistrationCreationServiceTrait;

class AvailabilityCount extends FieldItemList {

  use ComputedItemListTrait;
  use RegistrationCreationServiceTrait;

  /**
   * {@inheritDoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    $this->list[0] = $this->createItem(0, $this->getRegistrationCreationService($entity)->retrieveAvailability());
  }

}
