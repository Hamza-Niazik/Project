<?php

namespace Drupal\recurring_events\Plugin\migrate\process;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Transforms an array of dates to the format expected by recurring_events.
 *
 * The recurring_date plugin takes an array of event dates and converts it to
 * the structure used by one of the RecurringEventsFieldTypeInterface fields
 * (e.g., WeeklyRecurringDate), or to a 'daterange' field if the recurrence type
 * could not be determined. This can then be imported into an eventseries (along
 * with an appropriately set recur_type field) using the EntityEventSeries
 * destination plugin via its 'recurring_date_field' configuration key.
 *
 * The $value is expected to be an array of arrays. Each child array represents
 * an event instance and should contain one or two values representing the start
 * and end datetimes, respectively. For example:
 *
 * @code
 * $value = [
 *   [
 *     '20220719T120000',
 *     '20220719T130000',
 *   ]
 * ]
 * @endcode
 *
 * Alternatively, each child array can be associative and optionally include
 * values for timezone and an iCalendar RRULE. For example:
 *
 * @code
 * $value = [
 *   [
 *     'start' => '20220719T120000',
 *     'end' => '20220719T130000',
 *     'rrule' => 'FREQ=WEEKLY;UNTIL=20220802T130000Z',
 *     'timezone' => 'America/Detroit',
 *   ]
 * ]
 * @endcode
 *
 * In the above case, use the configuration keys (below) to tell the plugin
 * which keys in the child arrays represent which values. Also, please note:
 *   - If the key specified by 'value_key' in the configuration is not found in
 *     the child array, the child array will be assumed to be non-associative.
 *   - Only the RRULE and timezone values on the first element in the $value
 *     array will be used.
 *   - The 'rrule' value can be an iCalendar RRULE string or an array as
 *     returned by RRuleHelper::parseRule.
 *
 * Available configuration keys:
 *   - value_key: The key in the source child arrays that represents the start
 *     date. Defaults to 'value'.
 *   - end_value_key: The key in the source child arrays that represents the end
 *     date. Defaults to 'end_value'.
 *   - rrule_key: The key in the source child arrays that represents an RRULE.
 *     Defaults to 'rrule'.
 *   - timezone_key: The key in the source child arrays that represents the
 *     timezone. Defaults to 'timezone'.
 *   - default_timezone: The timezone to use if none is provided for an event
 *     instance. Defaults to 'UTC'.
 *
 * Usage:
 *
 * @code
 * process:
 *   recurring_date_field:
 *     plugin: recurring_date
 *     source: dates
 *     value_key: start
 *     end_value_key: end
 *     rrule_key: rrule
 *     timezone_key: timezone
 *     default_timezone: 'America/New_York'
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "recurring_date",
 *   handle_multiples = TRUE
 * )
 */
class RecurringDate extends ProcessPluginBase {

  /**
   * Default event duration (in seconds).
   *
   * Used for events that have no end date in the source event.
   */
  const DEFAULT_DURATION = 60 * 60;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException('Input should be an array.');
    }
    return self::calculateRecurringDateValues($value, $this->configuration);
  }

  /**
   * Converts date data including RRULE values to the format used by module.
   *
   * This method is provided as static so that it can be easily called by the
   * EntityEventSeries destination plugin when migrating from a Drupal 7 date
   * source field (via that plugin's 'source_date_field' option).
   *
   * @param array $source
   *   The date data.
   * @param array $configuration
   *   The plugin configuration.
   *
   * @return array
   *   The data in recurring date field format.
   *
   * @throws \Drupal\migrate\MigrateException
   *   If the $source does not contain any values.
   */
  public static function calculateRecurringDateValues(array $source, array $configuration): array {
    // @todo Fix problem of specifying what months here.
    // @todo Fix problem of no yearly pattern here.
    if (!$num_dates = count($source)) {
      throw new MigrateException('Date values are empty.');
    }

    $value_key = $configuration['value_key'] ?? 'value';
    $end_value_key = $configuration['end_value_key'] ?? 'end_value';
    $rrule_key = $configuration['rrule_key'] ?? 'rrule';
    $timezone_key = $configuration['timezone_key'] ?? 'timezone';
    $default_timezone = $configuration['default_timezone'] ?? 'UTC';

    $first = $source[0];
    $last = $source[$num_dates - 1];

    // Take the timezone from the first element, if provided.
    $timezone = empty($first[$timezone_key]) ? $default_timezone : $first[$timezone_key];
    $source_timezone = new \DateTimeZone($timezone);
    $storage_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);

    // Check whether the date data is non-associative.
    if (!isset($first[$value_key])) {
      $value_key = 0;
      $end_value_key = 1;
    }

    // Get values for the first event in the series.
    $start_event = new DrupalDateTime($first[$value_key], $source_timezone);
    $end_key = (!empty($first[$end_value_key])) ? $end_value_key : $value_key;
    $end_event = new DrupalDateTime($first[$end_key], $source_timezone);
    if (!$duration = $end_event->getTimestamp() - $start_event->getTimestamp()) {
      $duration = self::DEFAULT_DURATION;
      $end_event->add(new \DateInterval('PT' . $duration . 'S'));
    }

    // Check for an RRULE.
    $rule_in = empty($first[$rrule_key]) ? NULL : $first[$rrule_key];
    if ($rule_in) {
      $rrule = is_array($rule_in) ? $rule_in : RRuleHelper::parseRule($rule_in);
    }
    else {
      $rrule = [];
    }

    if (empty($rrule['UNTIL'])) {
      // Get values for the last event in the series.
      $end_series = new DrupalDateTime($last[$value_key], $source_timezone);
    }
    else {
      // Use the UNTIL value as the last event in the series.
      $end_series = new DrupalDateTime($rrule['UNTIL'], $source_timezone);
    }
    // Adjust the timezone before storing values into the database.\
    $start_event->setTimezone($storage_timezone);
    $end_event->setTimezone($storage_timezone);
    $end_series->setTimezone($storage_timezone);

    // Set the recurring date field data.
    $recurrence_config = [];
    $freq = $rrule['FREQ'] ?? NULL;
    switch ($freq) {

      // Set options for a weekly_recurring_date field.
      case 'WEEKLY':
        $recurrence_config = [
          'value' => $start_event->format(RRuleHelper::DATETIME_FORMAT),
          'end_value' => $end_series->format(RRuleHelper::DATETIME_FORMAT),
          'time' => $start_event->format('h:i a'),
          'end_time' => $end_event->format('h:i a'),
          'duration' => $duration,
          'duration_or_end_time' => 'end_time',
          'days' => $rrule['BYDAY']['days'] ?? strtolower($start_event->format('l')),
        ];
        break;

      // Set options for a monthly_recurring_date field.
      case 'MONTHLY':
        $recurrence_config = [
          'value' => $start_event->format(RRuleHelper::DATETIME_FORMAT),
          'end_value' => $end_series->format(RRuleHelper::DATETIME_FORMAT),
          'time' => $start_event->format('h:i a'),
          'end_time' => $end_event->format('h:i a'),
          'duration' => $duration,
          'duration_or_end_time' => 'end_time',
          'days' => $rrule['BYDAY']['days'] ?? strtolower($start_event->format('l')),
          'type' => (!empty($rrule['BYMONTHDAY'])) ? 'monthday' : 'weekday',
          'day_occurrence' => $rrule['BYDAY']['day_occurrence'] ?? NULL,
          'day_of_month' => $rrule['BYMONTHDAY'] ?? NULL,
        ];
        break;

      // Set options for a custom_date field.
      default:
        foreach ($source as $date) {
          $date_start = new DrupalDateTime($date[$value_key], $source_timezone);
          $date_end = new DrupalDateTime($date[$end_value_key], $source_timezone);
          $recurrence_config[] = [
            'value' => $date_start->setTimezone($storage_timezone)->format(RRuleHelper::DATETIME_FORMAT),
            'end_value' => $date_end->setTimezone($storage_timezone)->format(RRuleHelper::DATETIME_FORMAT),
          ];
        }
    }
    return $recurrence_config;
  }

}
