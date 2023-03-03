<?php

namespace Drupal\recurring_events_registration\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to show the count of registrations for event instances.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("eventinstance_registration_count")
 */
class EventInstanceRegistrationCount extends FieldPluginBase {

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
    return $event->get('registration_count')->getValue()[0]['value'] ?? 0;
  }

}
