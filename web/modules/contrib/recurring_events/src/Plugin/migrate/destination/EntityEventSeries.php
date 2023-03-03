<?php

namespace Drupal\recurring_events\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Row;
use Drupal\recurring_events\Entity\EventSeries;
use Drupal\recurring_events\Plugin\migrate\process\RecurringDate;
use Drupal\recurring_events\Plugin\migrate\process\RRuleHelper;

/**
 * The 'entity:eventseries' destination plugin for Recurring Events.
 *
 * Usage:
 *
 * When importing from a Drupal 7 source, use a config similar to the example
 * below (where 'field_event_datetime' is the name of the source date field).
 *
 * @code
 * destination:
 *   plugin: 'entity:eventseries'
 *   default_bundle: default
 *   source_date_field: field_event_datetime
 *   source_timezone: 'America/New_York'
 * @endcode
 *
 * This will directly convert the source field to populate the recurrence
 * options on the event series.
 *
 * If you are instead importing from a non-Drupal 7 source and want to use the
 * process pipeline to construct the recur_type, recurrence options, and
 * excluded/included dates data, you can use the following config (where 'dates'
 * is a destination field built using the 'recurring_date' process plugin).
 *
 * @code
 * destination:
 *   plugin: 'entity:eventseries'
 *   default_bundle: default
 *   recurring_date_field: dates
 * @endcode
 *
 * This will rename the recurring_date_field to match the recur_type before the
 * destination entity is saved. For example:
 *   - If the recur_type is 'weekly_recurring_date', the recurring_date_field
 *     will be renamed to 'weekly_recurring_date' before saving.
 *   - If the recur_type is 'custom', the recurring_date_field will be renamed
 *     to 'custom_date' before saving.
 *
 * Finally, if you simply want to pass your processed destination fields through
 * without any modification (for example, if you are using recur_type = 'custom'
 * for all imported events and constructing your own 'custom_date' field), use
 * the following config.
 *
 * @code
 * destination:
 *   plugin: 'entity:eventseries'
 *   default_bundle: default
 * @endcode
 *
 * Available configuration keys:
 *   - source_date_field: (optional) When importing from a Drupal 7 source, this
 *     is the name of the source field to use for the date recurrence pattern.
 *   - source_timezone: (optional, but required when using 'source_date_field')
 *     The timezone for the source_date_field data.
 *   - recurring_date_field: (optional) When using the recurring_date process
 *     plugin to construct recurrence pattern data, this is the name of the
 *     destination field that contains that plugin's output. In this case, it is
 *     assumed that a 'recur_type' destination field has also been properly set.
 *
 * @MigrateDestination(
 *   id = "entity:eventseries"
 * )
 */
class EntityEventSeries extends EntityContentBase {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    if (isset($this->configuration['source_date_field'])) {
      $source = $row->getSourceProperty($this->configuration['source_date_field']);
      $this->setRecurringDateValues($source, $this->configuration['source_timezone'], $row);
    }
    elseif (isset($this->configuration['recurring_date_field'])) {
      $recurring_date_field = $row->getDestinationProperty($this->configuration['recurring_date_field']);
      $recur_type = $row->getDestinationProperty('recur_type');
      $this->setRecurringDateField($row, $recur_type, $recurring_date_field);
    }
    return parent::import($row, $old_destination_id_values);
  }

  /**
   * Adds recurrence data to the row in a field that matches the recur_type.
   *
   * @param \Drupal\migrate\Row $row
   *   The current row being imported.
   * @param string $recur_type
   *   The recur_type for the eventseries destination entity.
   * @param array $recurring_date_field
   *   An array containing recurrence field data.
   *
   * @see \Drupal\recurring_events\Plugin\migrate\process\RecurringDate
   */
  private function setRecurringDateField(Row $row, string $recur_type, array $recurring_date_field) {
    $recurring_date_field_name = ($recur_type === 'custom') ? 'custom_date' : $recur_type;
    $row->setDestinationProperty($recurring_date_field_name, $recurring_date_field);
  }

  /**
   * Set destination recurring date values directly on the row.
   *
   * @param array $source
   *   The source date field data.
   * @param string $source_timezone
   *   The timezone for the source dates.
   * @param \Drupal\migrate\Row $row
   *   The current row being imported.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  private function setRecurringDateValues(array $source, string $source_timezone, Row $row) {
    // Set the recur_type.
    $rrule_string = $source[0]['rrule'] ?? NULL;
    $rrule = isset($rrule_string) ? RRuleHelper::parseRule($rrule_string) : [];
    $recur_type = RRuleHelper::getRecurType($rrule);
    $row->setDestinationProperty('recur_type', $recur_type);

    // Set the recurrence options.
    $options = RecurringDate::calculateRecurringDateValues($source, [
      'value_key' => 'value',
      'end_value_key' => 'value2',
      'rrule_key' => 'rrule',
      'default_timezone' => $source_timezone,
    ]);
    $this->setRecurringDateField($row, $recur_type, $options);

    if (!empty($rrule['EXDATE'])) {
      $row->setDestinationProperty('excluded_dates', $rrule['EXDATE']);
    }

    // @todo Fix problem of how to set this in D7 here.
    // if (!empty($rrule['INCDATE'])) {
    // $row->setDestinationProperty('included_dates', $rrule['INCDATE']);
    // }
    // $row->setDestinationProperty('event_registration', ['value' => 0]);
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    $entity = $this->storage->load(reset($destination_identifier));
    if ($entity && $entity instanceof EventSeries) {
      $instances = $entity->event_instances->referencedEntities();
      // Allow other modules to react prior to deleting all instances after a
      // date configuration change.
      \Drupal::moduleHandler()->invokeAll('recurring_events_pre_delete_instances', [$entity]);
      // Loop through all instances and remove them.
      foreach ($instances as $instance) {
        $instance->delete();
      }
      // Allow other modules to react after deleting all instances after a date
      // configuration change.
      \Drupal::moduleHandler()->invokeAll('recurring_events_post_delete_instances', [$entity]);
    }
    parent::rollback($destination_identifier);
  }

}
