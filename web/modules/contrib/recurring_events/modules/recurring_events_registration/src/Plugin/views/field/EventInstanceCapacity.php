<?php

namespace Drupal\recurring_events_registration\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\recurring_events_registration\RegistrationCreationService;

/**
 * Field handler to show the availability of registrations for event instances.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("eventinstance_capacity")
 */
class EventInstanceCapacity extends FieldPluginBase {

  /**
   * The registration creation service.
   *
   * @var \Drupal\recurring_events_registration\RegistrationCreationService
   */
  protected $registrationCreationService;

  /**
   * Constructs a new EventInstanceCapacity object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\recurring_events_registration\RegistrationCreationService $registration_creation_service
   *   The registration creation service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RegistrationCreationService $registration_creation_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->registrationCreationService = $registration_creation_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('recurring_events_registration.creation_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $event = $values->_entity;
    $this->registrationCreationService->setEventInstance($event);
    $capacity = (int) $this->registrationCreationService->getEventSeries()->event_registration->capacity;
    if ($capacity === -1) {
      $capacity = $this->t('Unlimited');
    }
    return $capacity;
  }

}
