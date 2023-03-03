<?php

namespace Drupal\recurring_events\Plugin\migrate\process;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;

/**
 * Helper methods for recurring_events process plugins that handle RRULEs.
 */
class RRuleHelper {

  /**
   * The datetime format used by recurring_events.
   */
  const DATETIME_FORMAT = 'Y-m-d\TH:i:s';

  /**
   * Map RRULE days to the values used by recurring_events.
   */
  const DAYS_OF_WEEK_TR = [
    'MO' => 'monday',
    'TU' => 'tuesday',
    'WE' => 'wednesday',
    'TH' => 'thursday',
    'FR' => 'friday',
    'SA' => 'saturday',
    'SU' => 'sunday',
  ];

  /**
   * Map RRULE BYDAY values to the values used by recurring_events.
   */
  const BYDAY_TR = [
    '+1' => 'first',
    '+2' => 'second',
    '+3' => 'third',
    '+4' => 'fourth',
    '-1' => 'last',
  ];

  /**
   * Map RRULE FREQ values to the recur_type values used by recurring_events.
   *
   * @todo Add the other recur_type options.
   */
  const FREQ_TR = [
    'WEEKLY' => 'weekly_recurring_date',
    'MONTHLY' => 'monthly_recurring_date',
  ];

  /**
   * Parse an RRULE into an array.
   *
   * @param string $rrule
   *   The RRULE to parse.
   *
   * @return array
   *   An array of key-value pairs representing the RRULE's attributes.
   *
   * @throws \Drupal\Component\Serialization\Exception\InvalidDataTypeException
   *   If the rule could not be parsed.
   */
  public static function parseRule(string $rrule): array {
    if (!$attrs = array_filter(explode(';', preg_replace('/^(?:RRULE|EXRULE):/i', '', str_replace("\n", ';', $rrule))))) {
      // @todo Fix problem of what type of exception should be thrown here.
      throw new InvalidDataTypeException('Invalid RRULE.');
    }

    $options = [];
    foreach ($attrs as $attr) {
      [$key, $value] = preg_split('/[=:]/', $attr);
      $key = strtoupper($key);
      switch ($key) {

        case 'COUNT':
        case 'INTERVAL':
        case 'BYSETPOS':
        case 'BYMONTHDAY':
        case 'BYYEARDAY':
        case 'BYWEEKNO':
        case 'BYHOUR':
        case 'BYMINUTE':
        case 'BYSECOND':
        case 'FREQ':
        case 'WKST':
        case 'DTSTART':
        case 'TZID':
        case 'BYEASTER':
          $options[$key] = $value;
          break;

        case 'UNTIL':
          $options[$key] = self::formatDate($value);
          break;

        case 'BYDAY':
          if (preg_match('/([+-]\d)(.*)/', trim($value), $matches)) {
            $options['BYDAY']['day_occurrence'] = $matches[1] ?? NULL;
            $options['BYDAY']['day_occurrence'] = self::formatByDay($options['BYDAY']['day_occurrence']);
            $options['BYDAY']['days'] = $matches[2] ?? NULL;
            $options['BYDAY']['days'] = self::formatDaysOfWeek($options['BYDAY']['days']);
          }
          else {
            $options['BYDAY']['days'] = self::formatDaysOfWeek(trim($value));
          }
          break;

        case 'BYMONTH':
        case 'BYWEEKDAY':
          $value = self::formatDaysOfWeek($value);
          $options[$key] = array_filter(explode(',', $value));
          break;

        case 'EXDATE':
          foreach (array_filter(explode(',', $value)) as $datetime) {
            $options['EXDATE'][] = [
              'value' => self::formatDate($datetime, 'Y-m-d'),
              'end_value' => self::formatDate($datetime, 'Y-m-d'),
            ];
          }
          break;

        default:
          // @todo Fix problem of what type of exception should be thrown here.
          throw new InvalidDataTypeException(sprintf('Invalid RRULE attribute of "%s".', $key));
      }
    } // Loop through attributes.
    return $options;
  }

  /**
   * Get formatted date from RRULE data.
   *
   * @param string $value
   *   Date in format 20210107T050000Z.
   * @param string $format
   *   Date format for output.
   *
   * @return false|string
   *   Formatted date string.
   */
  public static function formatDate(string $value, string $format = self::DATETIME_FORMAT) {
    $timestamp = self::getTimestamp($value);
    if ($timestamp === FALSE) {
      return FALSE;
    }
    return date($format, $timestamp);
  }

  /**
   * Get timestamp for RRULE data.
   *
   * @param string $value
   *   Date in format 20210107T050000Z.
   *
   * @return false|int
   *   Unix timestamp.
   */
  public static function getTimestamp(string $value) {
    $value = trim(preg_replace('/[TZ]/', ' ', $value));
    return strtotime($value);
  }

  /**
   * Convert RRULE day values to format used by recurring_events.
   *
   * @param string $value
   *   Day(s) in RRULE format (e.g. MO,TU).
   *
   * @return string
   *   Days in recurring_events format (e.g. monday,tuesday).
   */
  public static function formatDaysOfWeek(string $value): string {
    return strtr($value, self::DAYS_OF_WEEK_TR);
  }

  /**
   * Convert RRULE format for BYDAY to format used by recurring_events.
   *
   * @param string $value
   *   BYDAY values like "+1" in "+1TH".
   *
   * @return string
   *   BYDAY in recurring_events format (e.g. "first").
   */
  public static function formatByDay(string $value): string {
    return strtr($value, self::BYDAY_TR);
  }

  /**
   * Returns the recurring_events recur_type for the given RRULE.
   *
   * @param string|array $rrule
   *   An RRULE string, or an array as returned by RRuleHelper::parseRule.
   *
   * @return string
   *   A recurring_events recur_type; e.g., 'weekly_recurring_date'. If the
   *   RRULE could not be parsed or is unsupported, returns 'custom'.
   */
  public static function getRecurType($rrule): string {
    try {
      $parsed_rrule = is_array($rrule) ? $rrule : RRuleHelper::parseRule($rrule);
      $freq = $parsed_rrule['FREQ'] ?? NULL;
    }
    catch (InvalidDataTypeException $e) {
      $freq = NULL;
    }
    return self::FREQ_TR[$freq] ?? 'custom';
  }

}
