<?php

namespace Drupal\recurring_events_registration\Traits;

use Drupal\recurring_events\Entity\EventInstance;
use Drupal\recurring_events_registration\RegistrationCreationService;

/**
 * Trait RegistrationCreationServiceTrait.
 *
 * @package Drupal\recurring_events_registration\Traits
 */
trait RegistrationCreationServiceTrait {

  /**
   * @var RegistrationCreationService
   */
  protected $registration_creation_service;

  /**
   * Helper to get a registration creation service given an event instance.
   *
   * @param EventInstance $entity
   *
   * @return RegistrationCreationService
   */
  protected function getRegistrationCreationService($entity) {
    if (!$this->registration_creation_service) {
      $this->registration_creation_service = \Drupal::service('recurring_events_registration.creation_service');
      $this->registration_creation_service->setEventInstance($entity);
    }

    return $this->registration_creation_service;
  }

}
