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
 * Plugin implementation of the 'monthly_recurring_date' field type.
 *
 * @FieldType (
 *   id = "monthly_recurring_date",
 *   label = @Translation("Monthly Event"),
 *   description = @Translation("Stores a monthly recurring date configuration"),
 *   default_widget = "monthly_recurring_date",
 *   default_formatter = "monthly_recurring_date"
 * )
 */
class MonthlyRecurringDate extends WeeklyRecurringDate implements RecurringEventsFieldTypeInterface {

  use RecurringEventsFieldTrait;

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['type'] = [
      'type' => 'varchar',
      'length' => 20,
      'not null' => TRUE,
    ];

    $schema['columns']['day_occurrence'] = [
      'type' => 'varchar',
      'length' => 255,
    ];

    $schema['columns']['days']['not null'] = FALSE;

    $schema['columns']['day_of_month'] = [
      'type' => 'varchar',
      'length' => 255,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $type = $this->get('type')->getValue();
    $occurrence = $this->get('day_occurrence')->getValue();
    $day_of_month = $this->get('day_of_month')->getValue();
    return parent::isEmpty() && empty($type) && empty($occurrence) && empty($day_of_month);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    // Add our properties.
    $properties['type'] = DataDefinition::create('string')
      ->setLabel(t('Event Recurrence Scheduling'))
      ->setDescription(t('Whether this event recurs based on weekdays, or days of the month'));

    $properties['day_occurrence'] = DataDefinition::create('string')
      ->setLabel(t('Day Occurrence'))
      ->setDescription(t('Which occurence of the day(s) of the week should event take place'));

    $properties['day_of_month'] = DataDefinition::create('string')
      ->setLabel(t('Day of Month'))
      ->setDescription(t('The days of the month on which the event takes place'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function convertEntityConfigToArray(EventSeries $event) {
    $config = [];
    $config['start_date'] = $event->getMonthlyStartDate();
    $config['end_date'] = $event->getMonthlyEndDate();
    $config['time'] = strtoupper($event->getMonthlyStartTime());
    $config['end_time'] = strtoupper($event->getMonthlyEndTime());
    $config['duration'] = $event->getMonthlyDuration();
    $config['duration_or_end_time'] = $event->getMonthlyDurationOrEndTime();
    $config['monthly_type'] = $event->getMonthlyType();

    switch ($event->getMonthlyType()) {
      case 'weekday':
        $config['day_occurrence'] = $event->getMonthlyDayOccurrences();
        $config['days'] = $event->getMonthlyDays();
        break;

      case 'monthday':
        $config['day_of_month'] = $event->getMonthlyDayOfMonth();
        break;
    }
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function convertFormConfigToArray(FormStateInterface $form_state) {
    $config = [];

    $user_timezone = new \DateTimeZone(date_default_timezone_get());
    $user_input = $form_state->getValues();

    $time = $user_input['monthly_recurring_date'][0]['time'];
    if (is_array($time)) {
      $temp = DrupalDateTime::createFromFormat('H:i:s', $time['time']);
      $time = $temp->format('h:i A');
    }
    $time_parts = static::convertTimeTo24hourFormat($time);
    $timestamp = implode(':', $time_parts);

    $user_input['monthly_recurring_date'][0]['value']->setTimezone($user_timezone);
    $start_timestamp = $user_input['monthly_recurring_date'][0]['value']->format('Y-m-d') . 'T' . $timestamp;
    $start_date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $start_timestamp, $user_timezone);
    $start_date->setTime(0, 0, 0);

    $end_time = $user_input['monthly_recurring_date'][0]['end_time']['time'];
    if (is_array($end_time)) {
      $temp = DrupalDateTime::createFromFormat('H:i:s', $end_time['time']);
      $end_time = $temp->format('h:i A');
    }
    $end_time_parts = static::convertTimeTo24hourFormat($end_time);
    $end_timestamp = implode(':', $end_time_parts);

    $user_input['monthly_recurring_date'][0]['end_value']->setTimezone($user_timezone);
    $end_timestamp = $user_input['monthly_recurring_date'][0]['end_value']->format('Y-m-d') . 'T' . $end_timestamp;
    $end_date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $end_timestamp, $user_timezone);
    $end_date->setTime(0, 0, 0);

    $config['start_date'] = $start_date;
    $config['end_date'] = $end_date;

    $config['time'] = strtoupper($time);
    $config['end_time'] = strtoupper($end_time);
    $config['duration'] = $user_input['monthly_recurring_date'][0]['duration'];
    $config['duration_or_end_time'] = $user_input['monthly_recurring_date'][0]['duration_or_end_time'];
    $config['monthly_type'] = $user_input['monthly_recurring_date'][0]['type'];

    switch ($config['monthly_type']) {
      case 'weekday':
        $config['day_occurrence'] = array_filter(array_values($user_input['monthly_recurring_date'][0]['day_occurrence']));
        $config['days'] = array_filter(array_values($user_input['monthly_recurring_date'][0]['days']));
        break;

      case 'monthday':
        $config['day_of_month'] = array_filter(array_values($user_input['monthly_recurring_date'][0]['day_of_month']));
        break;
    }

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function buildDiffArray(array $entity_config, array $form_config) {
    $diff = parent::buildDiffArray($entity_config, $form_config);

    if ($entity_config['type'] === 'monthly_recurring_date') {
      if (($entity_config['monthly_type'] ?? '') !== ($form_config['monthly_type'] ?? '')) {
        $diff['monthly_type'] = [
          'label' => t('Monthly Type'),
          'stored' => $entity_config['monthly_type'] ?? '',
          'override' => $form_config['monthly_type'] ?? '',
        ];
      }
      if ($entity_config['monthly_type'] === 'weekday') {
        if (($entity_config['day_occurrence'] ?? []) !== ($form_config['day_occurrence'] ?? [])) {
          $diff['day_occurrence'] = [
            'label' => t('Day Occurrence'),
            'stored' => implode(',', ($entity_config['day_occurrence'] ?? [])),
            'override' => implode(',', ($form_config['day_occurrence'] ?? [])),
          ];
        }
        if (($entity_config['days'] ?? []) !== ($form_config['days'] ?? [])) {
          $diff['days'] = [
            'label' => t('Days'),
            'stored' => implode(',', ($entity_config['days'] ?? [])),
            'override' => implode(',', ($form_config['days'] ?? [])),
          ];
        }
      }
      else {
        if (($entity_config['day_of_month'] ?? []) !== ($form_config['day_of_month'] ?? [])) {
          $diff['day_of_month'] = [
            'label' => t('Day of the Month'),
            'stored' => implode(',', ($entity_config['day_of_month'] ?? [])),
            'override' => implode(',', ($form_config['day_of_month'] ?? [])),
          ];
        }
      }
    }

    return $diff;
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateInstances(array $form_data) {
    $dates = $events_to_create = [];
    $time_parts = static::convertTimeTo24hourFormat($form_data['time']);
    $utc_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);

    if (!empty($form_data['monthly_type'])) {
      switch ($form_data['monthly_type']) {
        case 'weekday':
          // Loop through each weekday occurrence and weekday.
          if (!empty($form_data['day_occurrence']) && !empty($form_data['days'])) {
            foreach ($form_data['day_occurrence'] as $occurrence) {
              foreach ($form_data['days'] as $weekday) {
                // Find the occurrence of the specific weekdays within
                // each month.
                $day_occurrences = static::findWeekdayOccurrencesBetweenDates($occurrence, $weekday, $form_data['start_date'], $form_data['end_date']);
                $dates = array_merge($dates, $day_occurrences);
              }
            }
          }
          break;

        case 'monthday':
          foreach ($form_data['day_of_month'] as $day_of_month) {
            $days_of_month = static::findMonthDaysBetweenDates($day_of_month, $form_data['start_date'], $form_data['end_date']);
            $dates = array_merge($dates, $days_of_month);
          }
          break;

      }

      // If valid recurring dates were found.
      if (!empty($dates)) {
        foreach ($dates as $monthly_date) {
          // Set the time of the start date to be the hours and
          // minutes.
          $monthly_date->setTime($time_parts[0], $time_parts[1]);
          // Create a clone of this date.
          $monthly_date_end = clone $monthly_date;
          // Check whether we are using a duration or end time.
          $duration_or_end_time = $form_data['duration_or_end_time'];
          switch ($duration_or_end_time) {
            case 'duration':
              // Add the number of seconds specified in the duration field.
              $monthly_date_end->modify('+' . $form_data['duration'] . ' seconds');
              break;

            case 'end_time':
              // Set the time to be the end time.
              $end_time_parts = static::convertTimeTo24hourFormat($form_data['end_time']);
              if (!empty($end_time_parts)) {
                $monthly_date_end->setTime($end_time_parts[0], $end_time_parts[1]);
              }
              break;
          }

          // Configure the timezone.
          $monthly_date->setTimezone($utc_timezone);
          $monthly_date_end->setTimezone($utc_timezone);

          // Set this event to be created.
          $events_to_create[$monthly_date->format('r')] = [
            'start_date' => $monthly_date,
            'end_date' => $monthly_date_end,
          ];
        }
      }
    }

    return $events_to_create;
  }

  /**
   * Find all the day-of-month occurrences between two dates.
   *
   * @param int $day_of_month
   *   The day of the month.
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The start date.
   * @param \Drupal\Core\Datetime\DrupalDateTime $end_date
   *   The end date.
   *
   * @return array
   *   An array of matching dates.
   */
  public static function findMonthDaysBetweenDates($day_of_month, DrupalDateTime $start_date, DrupalDateTime $end_date) {
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

    $day_to_check = $day_of_month;

    // If day of month is set to -1 that is the last day of the month, we need
    // to calculate how many days a month has.
    if ($day_of_month === '-1') {
      $day_to_check = $start->format('t');
    }

    // If the day of the month is after the start date.
    if ($start->format('d') < $day_to_check) {
      $new_date = clone $start;
      $curr_month = $new_date->format('m');
      $curr_year = $new_date->format('Y');

      // Check to see if that date is a valid date.
      if (!checkdate($curr_month, $day_to_check, $curr_year)) {
        // If not, go find the next valid date.
        $start = static::findNextMonthDay($day_of_month, $start);
      }
      else {
        // This is a valid date, so let us start there.
        $start->setDate($curr_year, $curr_month, $day_to_check);
      }
    }
    // If the day of the month is in the past.
    elseif ($start->format('d') > $day_to_check) {
      // Find the next valid start date.
      $start = static::findNextMonthDay($day_of_month, $start);
    }

    // Loop through each month checking to see if the day of the month is a
    // valid day, until the end date has been surpassed.
    while ($start->getTimestamp() <= $end_date->getTimestamp()) {
      // If we do not clone here we end up modifying the value of start in
      // the array and get some funky dates returned.
      $dates[] = clone $start;
      // Find the next valid event date.
      $start = static::findNextMonthDay($day_of_month, $start);
    }

    return $dates;
  }

  /**
   * Find the next day-of-month occurrence.
   *
   * @param int $day_of_month
   *   The day of the month.
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The start date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The next occurrence of a specific day of the month.
   */
  public static function findNextMonthDay($day_of_month, DrupalDateTime $date) {
    $new_date = clone $date;

    $curr_month = $new_date->format('m');
    $curr_year = $new_date->format('Y');
    $next_month = $curr_month;
    $next_year = $curr_year;

    do {
      $next_month = ($next_month + 1) % 12 ?: 12;
      $next_year = $next_month == 1 ? $next_year + 1 : $next_year;

      // If the desired day of the month is the last day, calculate what that
      // day is.
      if ($day_of_month === '-1') {
        $new_date->setDate($next_year, $next_month, '1');
        $day_of_month = $new_date->format('t');
      }
    } while (checkdate($next_month, $day_of_month, $next_year) === FALSE);

    $new_date->setDate($next_year, $next_month, $day_of_month);
    return $new_date;
  }

  /**
   * Find all the monthly occurrences of a specific weekday between two dates.
   *
   * @param string $occurrence
   *   Which occurrence of the weekday to find.
   * @param string $weekday
   *   The name of the day of the week.
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The start date.
   * @param \Drupal\Core\Datetime\DrupalDateTime $end_date
   *   The end date.
   *
   * @return array
   *   An array of matching dates.
   */
  public static function findWeekdayOccurrencesBetweenDates($occurrence, $weekday, DrupalDateTime $start_date, DrupalDateTime $end_date) {
    $dates = [];

    // Clone the date as we do not want to make changes to the original object.
    $start = clone $start_date;

    // If the start date is after the end date then we have an invalid range so
    // just return nothing.
    if ($start->getTimestamp() > $end_date->getTimestamp()) {
      return $dates;
    }

    // Grab the occurrence of the weekday we want for this current month.
    $start->modify($occurrence . ' ' . $weekday . ' of this month');

    // Make sure we didn't just go back in time.
    if ($start < $start_date) {
      // Go straight to next month.
      $start->modify($occurrence . ' ' . $weekday . ' of next month');
    }

    // Loop through a week at a time, storing the date in the array to return
    // until the end date is surpassed.
    while ($start->getTimestamp() <= $end_date->getTimestamp()) {
      // If we do not clone here we end up modifying the value of start in
      // the array and get some funky dates returned.
      $dates[] = clone $start;
      $start->modify($occurrence . ' ' . $weekday . ' of next month');
    }

    return $dates;
  }

}
