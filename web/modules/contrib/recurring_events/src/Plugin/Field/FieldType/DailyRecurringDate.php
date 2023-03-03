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
 * Plugin implementation of the 'daily_recurring_date' field type.
 *
 * @FieldType (
 *   id = "daily_recurring_date",
 *   label = @Translation("Daily Event"),
 *   description = @Translation("Stores a daily recurring date configuration"),
 *   default_widget = "daily_recurring_date",
 *   default_formatter = "daily_recurring_date"
 * )
 */
class DailyRecurringDate extends DateRangeItem implements RecurringEventsFieldTypeInterface {

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

    $schema['columns']['duration'] = [
      'type' => 'int',
      'unsigned' => TRUE,
    ];

    $schema['columns']['end_time'] = [
      'type' => 'varchar',
      'length' => 20,
    ];

    $schema['columns']['duration_or_end_time'] = [
      'type' => 'varchar',
      'length' => 255,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $time = $this->get('time')->getValue();
    $duration = $this->get('duration')->getValue();
    $end_time = $this->get('time')->getValue();
    $duration_or_end_time = $this->get('duration_or_end_time')->getValue();
    return parent::isEmpty() && empty($time) && empty($duration)
      && empty($end_time) && empty($duration_or_end_time);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    // Add our properties.
    $properties['time'] = DataDefinition::create('string')
      ->setLabel(t('Time'))
      ->setDescription(t('The time the event begins'));

    $properties['duration'] = DataDefinition::create('integer')
      ->setLabel(t('Duration'))
      ->setDescription(t('The duration of the event in minutes'));

    $properties['end_time'] = DataDefinition::create('string')
      ->setLabel(t('End Time'))
      ->setDescription(t('The time the event ends'));

    $properties['duration_or_end_time'] = DataDefinition::create('string')
      ->setLabel(t('Duration or End Time'))
      ->setDescription(t('Select whether to specify a duration or end time.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function convertEntityConfigToArray(EventSeries $event) {
    $config = [];
    $config['start_date'] = $event->getDailyStartDate();
    $config['end_date'] = $event->getDailyEndDate();
    $config['time'] = strtoupper($event->getDailyStartTime());
    $config['end_time'] = strtoupper($event->getDailyEndTime());
    $config['duration'] = $event->getDailyDuration();
    $config['duration_or_end_time'] = $event->getDailyDurationOrEndTime();
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function convertFormConfigToArray(FormStateInterface $form_state) {
    $config = [];

    $user_timezone = new \DateTimeZone(date_default_timezone_get());
    $user_input = $form_state->getValues();

    $time = $user_input['daily_recurring_date'][0]['time'];
    if (is_array($time)) {
      $temp = DrupalDateTime::createFromFormat('H:i:s', $time['time']);
      $time = $temp->format('h:i A');
    }
    $time_parts = static::convertTimeTo24hourFormat($time);
    $timestamp = implode(':', $time_parts);

    $user_input['daily_recurring_date'][0]['value']->setTimezone($user_timezone);
    $start_timestamp = $user_input['daily_recurring_date'][0]['value']->format('Y-m-d') . 'T' . $timestamp;
    $start_date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $start_timestamp, $user_timezone);
    $start_date->setTime(0, 0, 0);

    $end_time = $user_input['daily_recurring_date'][0]['end_time']['time'];
    if (is_array($end_time)) {
      $temp = DrupalDateTime::createFromFormat('H:i:s', $end_time['time']);
      $end_time = $temp->format('h:i A');
    }
    $end_time_parts = static::convertTimeTo24hourFormat($end_time);
    $end_timestamp = implode(':', $end_time_parts);

    $user_input['daily_recurring_date'][0]['end_value']->setTimezone($user_timezone);
    $end_timestamp = $user_input['daily_recurring_date'][0]['end_value']->format('Y-m-d') . 'T' . $end_timestamp;
    $end_date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $end_timestamp, $user_timezone);
    $end_date->setTime(0, 0, 0);

    $config['start_date'] = $start_date;
    $config['end_date'] = $end_date;

    $config['time'] = strtoupper($time);
    $config['end_time'] = strtoupper($end_time);
    $config['duration'] = $user_input['daily_recurring_date'][0]['duration'];
    $config['duration_or_end_time'] = $user_input['daily_recurring_date'][0]['duration_or_end_time'];

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function buildDiffArray(array $entity_config, array $form_config) {
    $diff = [];

    if (!empty($entity_config['start_date']) && !empty($form_config['start_date']) && $entity_config['start_date']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT) !== $form_config['start_date']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT)) {
      $diff['start_date'] = [
        'label' => t('Start Date'),
        'stored' => $entity_config['start_date']->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
        'override' => $form_config['start_date']->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
      ];
    }
    if (!empty($entity_config['end_date']) && !empty($form_config['end_date']) && $entity_config['end_date']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT) !== $form_config['end_date']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT)) {
      $diff['end_date'] = [
        'label' => t('End Date'),
        'stored' => $entity_config['end_date']->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
        'override' => $form_config['end_date']->format(DateTimeItemInterface::DATE_STORAGE_FORMAT),
      ];
    }
    if ((strtoupper($entity_config['time'] ?? '')) !== (strtoupper($form_config['time'] ?? ''))) {
      $diff['time'] = [
        'label' => t('Time'),
        'stored' => $entity_config['time'] ?? '',
        'override' => $form_config['time'] ?? '',
      ];
    }
    if (($entity_config['duration'] ?? '') !== ($form_config['duration'] ?? '')) {
      $diff['duration'] = [
        'label' => t('Duration'),
        'stored' => $entity_config['duration'] ?? '',
        'override' => $form_config['duration'] ?? '',
      ];
    }
    if ((strtoupper($entity_config['end_time'] ?? '')) !== (strtoupper($form_config['end_time'] ?? ''))) {
      $diff['end_time'] = [
        'label' => t('End Time'),
        'stored' => $entity_config['end_time'] ?? '',
        'override' => $form_config['end_time'] ?? '',
      ];
    }
    if (($entity_config['duration_or_end_time'] ?? '') !== ($form_config['duration_or_end_time'] ?? '')) {
      $diff['duration_or_end_time'] = [
        'label' => t('Duration or End Time'),
        'stored' => $entity_config['duration_or_end_time'] ?? '',
        'override' => $form_config['duration_or_end_time'] ?? '',
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

    $daily_dates = static::findDailyDatesBetweenDates($form_data['start_date'], $form_data['end_date']);
    $time_parts = static::convertTimeTo24hourFormat($form_data['time']);

    if (!empty($daily_dates)) {
      foreach ($daily_dates as $daily_date) {
        // Set the time of the start date to be the hours and minutes.
        $daily_date->setTime($time_parts[0], $time_parts[1]);
        // Create a clone of this date.
        $daily_date_end = clone $daily_date;
        // Check whether we are using a duration or end time.
        $duration_or_end_time = $form_data['duration_or_end_time'];
        switch ($duration_or_end_time) {
          case 'duration':
            // Add the number of seconds specified in the duration field.
            $daily_date_end->modify('+' . $form_data['duration'] . ' seconds');
            break;

          case 'end_time':
            // Set the time to be the end time.
            $end_time_parts = static::convertTimeTo24hourFormat($form_data['end_time']);
            if (!empty($end_time_parts)) {
              $daily_date_end->setTime($end_time_parts[0], $end_time_parts[1]);
            }
            break;
        }

        // Configure the storage timezone.
        $daily_date->setTimezone($utc_timezone);
        $daily_date_end->setTimezone($utc_timezone);

        // Set this event to be created.
        $events_to_create[$daily_date->format('r')] = [
          'start_date' => $daily_date,
          'end_date' => $daily_date_end,
        ];
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
   *
   * @return array
   *   An array of matching dates.
   */
  public static function findDailyDatesBetweenDates(DrupalDateTime $start_date, DrupalDateTime $end_date) {
    $dates = [];

    // Clone the date as we do not want to make changes to the original object.
    $start = clone $start_date;
    $end = clone $end_date;

    // We want to create events up to and including the last day, so modify the
    // end date to be midnight of the next day.
    $end->modify('midnight next day');

    // If the start date is after the end date then we have an invalid range so
    // just return nothing.
    if ($start->getTimestamp() > $end->getTimestamp()) {
      return $dates;
    }

    // Loop through a week at a time, storing the date in the array to return
    // until the end date is surpassed.
    while ($start->getTimestamp() < $end->getTimestamp()) {
      // If we do not clone here we end up modifying the value of start in
      // the array and get some funky dates returned.
      $dates[] = clone $start;
      $start->modify('+1 day');
    }

    return $dates;
  }

}
