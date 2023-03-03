<?php

namespace Drupal\recurring_events;

use Drupal\recurring_events\Entity\EventSeries;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface for recurring event type fields.
 */
interface RecurringEventsFieldTypeInterface {

  /**
   * Converts an EventSeries entity's recurring configuration to an array.
   *
   * @param Drupal\recurring_events\Entity\EventSeries $event
   *   The stored event series entity.
   *
   * @return array
   *   The recurring configuration as an array.
   */
  public static function convertEntityConfigToArray(EventSeries $event);

  /**
   * Converts a form state object's recurring configuration to an array.
   *
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of an updated event series entity.
   *
   * @return array
   *   The recurring configuration as an array.
   */
  public static function convertFormConfigToArray(FormStateInterface $form_state);

  /**
   * Calculate the event instances to create for a series.
   *
   * @param array $form_data
   *   The updated event series form data.
   */
  public static function calculateInstances(array $form_data);

  /**
   * Build diff array between stored entity and form state.
   *
   * @param array $entity_config
   *   The stored event series configuration.
   * @param array $form_config
   *   The form state, or original, event series configuration.
   *
   * @return array
   *   An array of differences.
   */
  public static function buildDiffArray(array $entity_config, array $form_config);

}
