<?php

namespace Drupal\recurring_events\Plugin;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * A trait to provide some time related reusable static methods.
 */
trait RecurringEventsFieldTrait {

  /**
   * Generate times based on specific intervals and min/max times.
   *
   * @return array
   *   An array of times suitable for a select list.
   */
  protected function getTimeOptions() {
    $times = [];

    $config = \Drupal::config('recurring_events.eventseries.config');

    // Take interval in minutes, and multiply it by 60 to convert to seconds.
    $interval = $config->get('interval') * 60;
    if ($interval) {
      $min_time = $config->get('min_time');
      $max_time = $config->get('max_time');
      $format = $config->get('time_format');

      $min_time = DrupalDateTime::createFromFormat('h:ia', $min_time);
      $max_time = DrupalDateTime::createFromFormat('h:ia', $max_time);

      // Convert the mininum time to a number of seconds after midnight.
      $lower_hour = $min_time->format('H') * 60 * 60;
      $lower_minute = $min_time->format('i') * 60;
      $lower = $lower_hour + $lower_minute;

      // Convert the maximum time to a number of seconds after midnight.
      $upper_hour = $max_time->format('H') * 60 * 60;
      $upper_minute = $max_time->format('i') * 60;
      $upper = $upper_hour + $upper_minute;

      $range = range($lower, $upper, $interval);
      $utc_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);

      foreach ($range as $time) {
        $time_option = DrupalDateTime::createFromTimestamp($time, $utc_timezone);
        $times[$time_option->format('h:i a', ['langcode' => 'en'])] = $time_option->format($format);
      }
    }

    \Drupal::moduleHandler()->alter('recurring_events_times', $times);

    return $times;
  }

  /**
   * Return durations for events.
   *
   * @return array
   *   An array of durations suitable for a select list.
   */
  protected function getDurationOptions() {
    $durations = [
      '900' => t('15 minutes'),
      '1800' => t('30 minutes'),
      '2700' => t('45 minutes'),
      '3600' => t('1 hour'),
      '5400' => t('1.5 hours'),
      '7200' => t('2 hours'),
      '9000' => t('2.5 hours'),
      '10800' => t('3 hours'),
      '12600' => t('3.5 hours'),
      '14400' => t('4 hours'),
      '16200' => t('4.5 hours'),
      '18000' => t('5 hours'),
      '19800' => t('5.5 hours'),
      '21600' => t('6 hours'),
      '25200' => t('7 hours'),
      '28800' => t('8 hours'),
      '32400' => t('9 hours'),
      '36000' => t('10 hours'),
      '39600' => t('11 hours'),
      '43200' => t('12 hours'),
      '46800' => t('13 hours'),
      '50400' => t('14 hours'),
      '54000' => t('15 hours'),
      '57600' => t('16 hours'),
      '61200' => t('17 hours'),
      '64800' => t('18 hours'),
      '68400' => t('19 hours'),
      '72000' => t('20 hours'),
      '75600' => t('21 hours'),
      '79200' => t('22 hours'),
      '82800' => t('23 hours'),
      '86400' => t('24 hours'),
    ];

    \Drupal::moduleHandler()->alter('recurring_events_durations', $durations);

    return $durations;
  }

  /**
   * Convert a time from 12 hour format to 24 hour format.
   *
   * @var string $time
   *   The time to convert to 24 hour format.
   *
   * @return array
   *   An array of time parts.
   */
  protected static function convertTimeTo24hourFormat($time) {
    $time_parts = [];
    $timestamp = strtotime($time);
    $time_24hr = \Drupal::service('date.formatter')->format($timestamp, 'html_time');

    // Split the start time up to separate out hours and minutes.
    $time_parts = explode(':', $time_24hr);

    return $time_parts;
  }

  /**
   * Return unit options for events.
   *
   * @return array
   *   An array of unit options suitable for a select list.
   */
  protected function getUnitOptions() {
    $units = [
      'second' => t('Second(s)'),
      'minute' => t('Minute(s)'),
      'hour' => t('Hour(s)'),
    ];

    \Drupal::moduleHandler()->alter('recurring_events_units', $units);

    return $units;
  }

}
