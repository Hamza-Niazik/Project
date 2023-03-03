<?php

namespace Drupal\recurring_events\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;
use Drupal\recurring_events\RecurringEventsFieldTypeInterface;
use Drupal\recurring_events\Entity\EventSeries;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\recurring_events\Plugin\RecurringEventsFieldTrait;

/**
 * Plugin implementation of the 'consecutive_recurring_date' field type.
 *
 * @FieldType (
 *   id = "consecutive_recurring_date",
 *   label = @Translation("Consecutive Event"),
 *   description = @Translation("Stores a consecutive recurring date configuration"),
 *   default_widget = "consecutive_recurring_date",
 *   default_formatter = "consecutive_recurring_date"
 * )
 */
class ConsecutiveRecurringDate extends DateRangeItem implements RecurringEventsFieldTypeInterface {

  use RecurringEventsFieldTrait;

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['time'] = [
      'type' => 'varchar',
      'length' => 20,
    ];

    $schema['columns']['end_time'] = [
      'type' => 'varchar',
      'length' => 20,
    ];

    $schema['columns']['duration'] = [
      'type' => 'int',
      'unsigned' => TRUE,
    ];

    $schema['columns']['duration_units'] = [
      'type' => 'varchar',
      'length' => 20,
    ];

    $schema['columns']['buffer'] = [
      'type' => 'int',
      'unsigned' => TRUE,
    ];

    $schema['columns']['buffer_units'] = [
      'type' => 'varchar',
      'length' => 20,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $time = $this->get('time')->getValue();
    $end_time = $this->get('end_time')->getValue();
    $duration = $this->get('duration')->getValue();
    $duration_units = $this->get('duration_units')->getValue();
    $buffer = $this->get('buffer')->getValue();
    $buffer_units = $this->get('buffer_units')->getValue();
    return parent::isEmpty() && empty($time) && empty($end_time)
      && empty($duration) && empty($duration_units)
      && empty($buffer) && empty($buffer_units);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    // Add our properties.
    $properties['time'] = DataDefinition::create('string')
      ->setLabel(t('Time'))
      ->setDescription(t('The time the events begin each day'));

    $properties['end_time'] = DataDefinition::create('string')
      ->setLabel(t('End Time'))
      ->setDescription(t('The time the events end each day'));

    $properties['duration'] = DataDefinition::create('integer')
      ->setLabel(t('Duration'))
      ->setDescription(t('The duration of the events'));

    $properties['duration_units'] = DataDefinition::create('string')
      ->setLabel(t('Duration Units'))
      ->setDescription(t('The duration unites of the events'));

    $properties['buffer'] = DataDefinition::create('integer')
      ->setLabel(t('Buffer'))
      ->setDescription(t('The time between consecutive events'));

    $properties['buffer_units'] = DataDefinition::create('string')
      ->setLabel(t('Buffer Units'))
      ->setDescription(t('The units used for the time between consecutive events'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function convertEntityConfigToArray(EventSeries $event) {
    $config = [];
    $config['start_date'] = $event->getConsecutiveStartDate();
    $config['end_date'] = $event->getConsecutiveEndDate();
    $config['time'] = strtoupper($event->getConsecutiveStartTime());
    $config['end_time'] = strtoupper($event->getConsecutiveEndTime());
    $config['duration'] = $event->getConsecutiveDuration();
    $config['duration_units'] = $event->getConsecutiveDurationUnits();
    $config['buffer'] = $event->getConsecutiveBuffer();
    $config['buffer_units'] = $event->getConsecutiveBufferUnits();
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function convertFormConfigToArray(FormStateInterface $form_state) {
    $config = [];

    $user_timezone = new \DateTimeZone(date_default_timezone_get());
    $user_input = $form_state->getValues();

    $time = $user_input['consecutive_recurring_date'][0]['time'];
    if (is_array($time)) {
      $temp = DrupalDateTime::createFromFormat('H:i:s', $time['time']);
      $time = $temp->format('h:i A');
    }

    $end_time = $user_input['consecutive_recurring_date'][0]['end_time'];
    if (is_array($end_time)) {
      $temp = DrupalDateTime::createFromFormat('H:i:s', $end_time['time']);
      $end_time = $temp->format('h:i A');
    }

    if (!empty($user_input['consecutive_recurring_date'][0]['value'])
      && !empty($user_input['consecutive_recurring_date'][0]['end_value'])
      && !empty($user_input['consecutive_recurring_date'][0]['time'])) {
      $time_parts = static::convertTimeTo24hourFormat($time);
      $timestamp = implode(':', $time_parts);
      $user_input['consecutive_recurring_date'][0]['value']->setTimezone($user_timezone);
      $start_timestamp = $user_input['consecutive_recurring_date'][0]['value']->format('Y-m-d') . 'T' . $timestamp;
      $start_date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $start_timestamp, $user_timezone);
      $start_date->setTime(0, 0, 0);

      $user_input['consecutive_recurring_date'][0]['end_value']->setTimezone($user_timezone);
      $end_timestamp = $user_input['consecutive_recurring_date'][0]['end_value']->format('Y-m-d') . 'T' . $timestamp;
      $end_date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $end_timestamp, $user_timezone);
      $end_date->setTime(0, 0, 0);

      $config['start_date'] = $start_date;
      $config['end_date'] = $end_date;

      $config['time'] = strtoupper($time);
      $config['end_time'] = strtoupper($end_time);
      $config['duration'] = $user_input['consecutive_recurring_date'][0]['duration'];
      $config['duration_units'] = $user_input['consecutive_recurring_date'][0]['duration_units'];
      $config['buffer'] = $user_input['consecutive_recurring_date'][0]['buffer'];
      $config['buffer_units'] = $user_input['consecutive_recurring_date'][0]['buffer_units'];
    }
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function buildDiffArray(array $entity_config, array $form_config) {
    $diff = [];
    if (!empty($entity_config['start_date']) && !empty($entity_config['end_date'])) {
      if ($entity_config['start_date']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT) !== $form_config['start_date']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT)) {
        $diff['start_date'] = [
          'label' => t('Start Date'),
          'stored' => $entity_config['start_date']->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
          'override' => $form_config['start_date']->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
        ];
      }
      if ($entity_config['end_date']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT) !== $form_config['end_date']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT)) {
        $diff['end_date'] = [
          'label' => t('End Date'),
          'stored' => $entity_config['end_date']->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
          'override' => $form_config['end_date']->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
        ];
      }
    }
    if ((strtoupper($entity_config['time'] ?? '')) !== (strtoupper($form_config['time'] ?? ''))) {
      $diff['time'] = [
        'label' => t('Time'),
        'stored' => $entity_config['time'] ?? '',
        'override' => $form_config['time'] ?? '',
      ];
    }
    if ((strtoupper($entity_config['end_time'] ?? '')) !== (strtoupper($form_config['end_time'] ?? ''))) {
      $diff['end_time'] = [
        'label' => t('End Time'),
        'stored' => $entity_config['end_time'] ?? '',
        'override' => $form_config['end_time'] ?? '',
      ];
    }
    if (($entity_config['duration'] ?? '') !== ($form_config['duration'] ?? '')) {
      $diff['duration'] = [
        'label' => t('Duration'),
        'stored' => $entity_config['duration'] ?? '',
        'override' => $form_config['duration'] ?? '',
      ];
    }
    if (($entity_config['duration_units'] ?? '') !== ($form_config['duration_units'] ?? '')) {
      $diff['duration_units'] = [
        'label' => t('Duration Units'),
        'stored' => $entity_config['duration_units'] ?? '',
        'override' => $form_config['duration_units'] ?? '',
      ];
    }
    if (($entity_config['buffer'] ?? '') !== ($form_config['buffer'] ?? '')) {
      $diff['buffer'] = [
        'label' => t('Buffer'),
        'stored' => $entity_config['buffer'] ?? '',
        'override' => $form_config['buffer'] ?? '',
      ];
    }
    if (($entity_config['buffer_units'] ?? '') !== ($form_config['buffer_units'] ?? '')) {
      $diff['buffer_units'] = [
        'label' => t('Buffer Units'),
        'stored' => $entity_config['buffer_units'] ?? '',
        'override' => $form_config['buffer_units'] ?? '',
      ];
    }

    return $diff;
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateInstances(array $form_data) {
    $events_to_create = [];
    $utc_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);

    if(!empty($form_data['start_date']) && !empty($form_data['end_date'])) {
      $daily_dates = static::findDailyDatesBetweenDates($form_data['start_date'], $form_data['end_date']);
      $time_parts = static::convertTimeTo24hourFormat($form_data['time']);

      if (!empty($daily_dates)) {
        foreach ($daily_dates as $daily_date) {
          // Set the time of the start date to be the hours and minutes.
          $daily_date->setTime($time_parts[0], $time_parts[1]);
          // Configure the right timezone.
          $daily_date->setTimezone($utc_timezone);
          $day_times = static::findSlotsBetweenTimes($daily_date, $form_data);

          if (!empty($day_times)) {
            foreach ($day_times as $day_time) {
              // Create a clone of this date.
              $daily_date_end = clone $day_time;
              // Add the number of seconds specified in the duration field.
              $daily_date_end->modify('+' . $form_data['duration'] . ' ' . $form_data['duration_units']);
              // Set this event to be created.
              $events_to_create[$day_time->format('r')] = [
                'start_date' => $day_time,
                'end_date' => $daily_date_end,
              ];
            }
          }
        }
      }
    }

    return $events_to_create;
  }

  /**
   * Find all the daily date occurrences between two dates.
   *
   * @param Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The start date.
   * @param Drupal\Core\Datetime\DrupalDateTime $end_date
   *   The end date.
   * @param bool $count_only
   *   Whether to only return a count.
   *
   * @return array|int
   *   An array of matching dates, or a count.
   */
  public static function findDailyDatesBetweenDates(DrupalDateTime $start_date, DrupalDateTime $end_date, $count_only = FALSE) {
    $dates = [];
    $count = 0;

    // Clone the date as we do not want to make changes to the original object.
    $start = clone $start_date;
    $end = clone $end_date;

    // We want to create events up to and including the last day, so modify the
    // end date to be midnight of the next day.
    $end->modify('midnight next day');

    // If the start date is after the end date then we have an invalid range so
    // just return nothing.
    if ($start->getTimestamp() > $end->getTimestamp()) {
      if ($count_only) {
        return $count;
      }
      return $dates;
    }

    // Loop through a week at a time, storing the date in the array to return
    // until the end date is surpassed.
    while ($start->getTimestamp() < $end->getTimestamp()) {
      // If we do not clone here we end up modifying the value of start in
      // the array and get some funky dates returned.
      if (!$count_only) {
        $dates[] = clone $start;
      }
      $start->modify('+1 day');
      $count++;
    }

    if ($count_only) {
      return $count;
    }
    return $dates;
  }

  /**
   * Find all the time slots between two times of a specific day.
   *
   * @param Drupal\Core\Datetime\DrupalDateTime $date
   *   The date.
   * @param array $form_data
   *   The form data used to find the time slots.
   * @param bool $count_only
   *   Whether to only return a count.
   *
   * @return array|int
   *   An array of time slots, or a count.
   */
  public static function findSlotsBetweenTimes(DrupalDateTime $date, array $form_data, $count_only = FALSE) {
    $slots = [];
    $count = 0;

    $max_time = clone $date;
    $user_timezone = new \DateTimeZone(date_default_timezone_get());
    $utc_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);

    $time_parts = static::convertTimeTo24hourFormat($form_data['end_time']);
    $max_time->setTimezone($user_timezone);
    $max_time->setTime($time_parts[0], $time_parts[1]);
    $max_time->setTimezone($utc_timezone);

    while ($date->getTimestamp() <= $max_time->getTimestamp()) {
      $duration = $form_data['duration'];
      $duration_units = $form_data['duration_units'];
      $buffer = $form_data['buffer'];
      $buffer_units = $form_data['buffer_units'];

      if (!$count_only) {
        $slots[] = clone $date;
      }
      $date->modify('+ ' . $duration . ' ' . $duration_units);
      $date->modify('+ ' . $buffer . ' ' . $buffer_units);
      $count++;
    }

    if ($count_only) {
      return $count;
    }
    return $slots;
  }

}
