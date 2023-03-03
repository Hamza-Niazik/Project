<?php

namespace Drupal\Tests\recurring_events\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\recurring_events\Plugin\Field\FieldType\MonthlyRecurringDate;

/**
 * @coversDefaultClass \Drupal\recurring_events\Plugin\Field\FieldType\MonthlyRecurringDate
 * @group recurring_events
 * @requires module field_inheritance
 */
class MonthlyRecurringDateTest extends KernelTestBase {

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  protected static $modules = [
    'datetime',
    'datetime_range',
    'field_inheritance',
    'options',
    'recurring_events',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('eventseries');
    $this->installEntitySchema('eventinstance');
    $this->installConfig([
      'field_inheritance',
      'recurring_events',
      'datetime',
      'system',
    ]);
  }

  /**
   * Tests MonthlyRecurringDate::findWeekdayOccurrencesBetweenDates().
   */
  public function testFindWeekdayOccurrencesBetweenDates() {
    // We want to test for generating all the monthdays in January and February.
    $start_date = new DrupalDateTime('2019-01-01 00:00:00');
    $end_date = new DrupalDateTime('2019-02-28 00:00:00');

    $weekdays = [
      'Monday',
      'Wednesday',
    ];

    $occurrences = [
      'first',
      'second',
      'third',
      'fourth',
      'last',
    ];

    $expected_dates = $actual_dates = [];

    $expected_date_objects = [
      'Monday' => [
        'first' => [
          new DrupalDateTime('2019-01-07 00:00:00'),
          new DrupalDateTime('2019-02-04 00:00:00'),
        ],
        'second' => [
          new DrupalDateTime('2019-01-14 00:00:00'),
          new DrupalDateTime('2019-02-11 00:00:00'),
        ],
        'third' => [
          new DrupalDateTime('2019-01-21 00:00:00'),
          new DrupalDateTime('2019-02-18 00:00:00'),
        ],
        'fourth' => [
          new DrupalDateTime('2019-01-28 00:00:00'),
          new DrupalDateTime('2019-02-25 00:00:00'),
        ],
        'last' => [
          new DrupalDateTime('2019-01-28 00:00:00'),
          new DrupalDateTime('2019-02-25 00:00:00'),
        ],
      ],
      'Wednesday' => [
        'first' => [
          new DrupalDateTime('2019-01-02 00:00:00'),
          new DrupalDateTime('2019-02-06 00:00:00'),
        ],
        'second' => [
          new DrupalDateTime('2019-01-09 00:00:00'),
          new DrupalDateTime('2019-02-13 00:00:00'),
        ],
        'third' => [
          new DrupalDateTime('2019-01-16 00:00:00'),
          new DrupalDateTime('2019-02-20 00:00:00'),
        ],
        'fourth' => [
          new DrupalDateTime('2019-01-23 00:00:00'),
          new DrupalDateTime('2019-02-27 00:00:00'),
        ],
        'last' => [
          new DrupalDateTime('2019-01-30 00:00:00'),
          new DrupalDateTime('2019-02-27 00:00:00'),
        ],
      ],
    ];

    foreach ($weekdays as $weekday) {
      foreach ($occurrences as $occurrence) {
        $date_objects[$weekday][$occurrence] = MonthlyRecurringDate::findWeekdayOccurrencesBetweenDates($occurrence, $weekday, $start_date, $end_date);
      }
    }

    // Because the objects themselves will be different we convert each of the
    // date time objects into an ISO standard date format for comparison.
    foreach ($expected_date_objects as $weekday => $occurrences) {
      foreach ($occurrences as $occurrence => $dates) {
        foreach ($dates as $date) {
          $expected_dates[$weekday][$occurrence][] = $date->format('r');
        }
      }
    }

    foreach ($date_objects as $weekday => $occurrences) {
      foreach ($occurrences as $occurrence => $dates) {
        foreach ($dates as $date) {
          $actual_dates[$weekday][$occurrence][] = $date->format('r');
        }
      }
    }

    $this->assertEquals($expected_dates, $actual_dates);
  }

  /**
   * Tests MonthlyRecurringDate::findMonthDaysBetweenDates().
   */
  public function testFindMonthDaysBetweenDates() {
    // We want to test for generating all the monthdays in January and February.
    $start_date = new DrupalDateTime('2019-01-01 00:00:00');
    $end_date = new DrupalDateTime('2019-02-28 00:00:00');

    $month_days = [
      '1',
      '29',
      '-1',
    ];

    $expected_dates = $actual_dates = [];

    $expected_date_objects = [
      '1' => [
        new DrupalDateTime('2019-01-01 00:00:00'),
        new DrupalDateTime('2019-02-01 00:00:00'),
      ],
      '29' => [
        new DrupalDateTime('2019-01-29 00:00:00'),
      ],
      '-1' => [
        new DrupalDateTime('2019-01-31 00:00:00'),
        new DrupalDateTime('2019-02-28 00:00:00'),
      ],
    ];

    foreach ($month_days as $day) {
      $date_objects[$day] = MonthlyRecurringDate::findMonthDaysBetweenDates($day, $start_date, $end_date);
    }

    // Because the objects themselves will be different we convert each of the
    // date time objects into an ISO standard date format for comparison.
    foreach ($expected_date_objects as $day => $dates) {
      foreach ($dates as $date) {
        $expected_dates[$day][] = $date->format('r');
      }
    }

    foreach ($date_objects as $day => $dates) {
      foreach ($dates as $date) {
        $actual_dates[$day][] = $date->format('r');
      }
    }

    $this->assertEquals($expected_dates, $actual_dates);
  }

}
