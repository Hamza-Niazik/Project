<?php

namespace Drupal\recurring_events\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\recurring_events\RecurringEventsFieldTypeInterface;
use Drupal\recurring_events\Entity\EventSeries;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\recurring_events\Plugin\RecurringEventsFieldTrait;

/**
 * Plugin implementation of the 'weekly_recurring_date' field type.
 *
 * @FieldType (
 *   id = "weekly_recurring_date",
 *   label = @Translation("Weekly Event"),
 *   description = @Translation("Stores a weekly recurring date configuration"),
 *   default_widget = "weekly_recurring_date",
 *   default_formatter = "weekly_recurring_date"
 * )
 */
class WeeklyRecurringDate extends DailyRecurringDate implements RecurringEventsFieldTypeInterface {

  use RecurringEventsFieldTrait;

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['days'] = [
      'type' => 'varchar',
      'length' => 255,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $days = $this->get('days')->getValue();
    return parent::isEmpty() && empty($days);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['days'] = DataDefinition::create('string')
      ->setLabel(t('Days'))
      ->setDescription(t('The days of the week on which this event occurs'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function convertEntityConfigToArray(EventSeries $event) {
    $config = [];
    $config['start_date'] = $event->getWeeklyStartDate();
    $config['end_date'] = $event->getWeeklyEndDate();
    $config['time'] = strtoupper($event->getWeeklyStartTime());
    $config['end_time'] = strtoupper($event->getWeeklyEndTime());
    $config['duration'] = $event->getWeeklyDuration();
    $config['duration_or_end_time'] = $event->getWeeklyDurationOrEndTime();
    $config['days'] = $event->getWeeklyDays();
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function convertFormConfigToArray(FormStateInterface $form_state) {
    $config = [];

    $user_timezone = new \DateTimeZone(date_default_timezone_get());
    $user_input = $form_state->getValues();

    $time = $user_input['weekly_recurring_date'][0]['time'];
    if (is_array($time)) {
      $temp = DrupalDateTime::createFromFormat('H:i:s', $time['time']);
      $time = $temp->format('h:i A');
    }
    $time_parts = static::convertTimeTo24hourFormat($time);
    $timestamp = implode(':', $time_parts);

    $user_input['weekly_recurring_date'][0]['value']->setTimezone($user_timezone);
    $start_timestamp = $user_input['weekly_recurring_date'][0]['value']->format('Y-m-d') . 'T' . $timestamp;
    $start_date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $start_timestamp, $user_timezone);
    $start_date->setTime(0, 0, 0);

    $end_time = $user_input['weekly_recurring_date'][0]['end_time']['time'];
    if (is_array($end_time)) {
      $temp = DrupalDateTime::createFromFormat('H:i:s', $end_time['time']);
      $end_time = $temp->format('h:i A');
    }
    $end_time_parts = static::convertTimeTo24hourFormat($end_time);
    $end_timestamp = implode(':', $end_time_parts);

    $user_input['weekly_recurring_date'][0]['end_value']->setTimezone($user_timezone);
    $end_timestamp = $user_input['weekly_recurring_date'][0]['end_value']->format('Y-m-d') . 'T' . $end_timestamp;
    $end_date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $end_timestamp, $user_timezone);
    $end_date->setTime(0, 0, 0);

    $config['start_date'] = $start_date;
    $config['end_date'] = $end_date;

    $config['time'] = strtoupper($time);
    $config['end_time'] = strtoupper($end_time);
    $config['duration'] = $user_input['weekly_recurring_date'][0]['duration'];
    $config['duration_or_end_time'] = $user_input['weekly_recurring_date'][0]['duration_or_end_time'];
    $config['days'] = array_filter(array_values($user_input['weekly_recurring_date'][0]['days']));
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function buildDiffArray(array $entity_config, array $form_config) {
    $diff = parent::buildDiffArray($entity_config, $form_config);
    if (($entity_config['days'] ?? []) !== ($form_config['days'] ?? [])) {
      $diff['days'] = [
        'label' => t('Days'),
        'stored' => implode(',', ($entity_config['days'] ?? [])),
        'override' => implode(',', ($form_config['days'] ?? [])),
      ];
    }

    return $diff;
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateInstances(array $form_data) {
    $dates = $events_to_create = [];
    $utc_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);

    // Loop through each weekday and find occurrences of it in the
    // date range provided.
    foreach ($form_data['days'] as $weekday) {
      $weekday_dates = static::findWeekdaysBetweenDates($weekday, $form_data['start_date'], $form_data['end_date']);
      $dates = array_merge($dates, $weekday_dates);
    }
    $time_parts = static::convertTimeTo24hourFormat($form_data['time']);

    if (!empty($dates)) {
      foreach ($dates as $weekly_date) {
        // Set the time of the start date to be the hours and minutes.
        $weekly_date->setTime($time_parts[0], $time_parts[1]);
        // Create a clone of this date.
        $weekly_date_end = clone $weekly_date;
        // Check whether we are using a duration or end time.
        $duration_or_end_time = $form_data['duration_or_end_time'];
        switch ($duration_or_end_time) {
          case 'duration':
            // Add the number of seconds specified in the duration field.
            $weekly_date_end->modify('+' . $form_data['duration'] . ' seconds');
            break;

          case 'end_time':
            // Set the time to be the end time.
            $end_time_parts = static::convertTimeTo24hourFormat($form_data['end_time']);
            if (!empty($end_time_parts)) {
              $weekly_date_end->setTime($end_time_parts[0], $end_time_parts[1]);
            }
            break;
        }

        // Set the storage timezone.
        $weekly_date->setTimezone($utc_timezone);
        $weekly_date_end->setTimezone($utc_timezone);

        // Set this event to be created.
        $events_to_create[$weekly_date->format('r')] = [
          'start_date' => $weekly_date,
          'end_date' => $weekly_date_end,
        ];
      }
    }

    return $events_to_create;
  }

  /**
   * Find all the weekday occurrences between two dates.
   *
   * @param string $weekday
   *   The name of the day of the week.
   * @param Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The start date.
   * @param Drupal\Core\Datetime\DrupalDateTime $end_date
   *   The end date.
   *
   * @return array
   *   An array of matching dates.
   */
  public static function findWeekdaysBetweenDates($weekday, DrupalDateTime $start_date, DrupalDateTime $end_date) {
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

    // If the start date is not the weekday we are seeking, jump to the next
    // instance of that weekday.
    if ($start->format('l') != ucwords($weekday)) {
      $start->modify('next ' . $weekday);
    }

    // Loop through a week at a time, storing the date in the array to return
    // until the end date is surpassed.
    while ($start->getTimestamp() < $end->getTimestamp()) {
      // If we do not clone here we end up modifying the value of start in
      // the array and get some funky dates returned.
      $dates[] = clone $start;
      $start->modify('+1 week');
    }

    return $dates;
  }

}
