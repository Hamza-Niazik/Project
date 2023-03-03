<?php

namespace Drupal\Tests\recurring_events\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\recurring_events\Plugin\Field\FieldType\WeeklyRecurringDate;

/**
 * @coversDefaultClass \Drupal\recurring_events\Plugin\Field\FieldType\WeeklyRecurringDate
 * @group recurring_events
 * @requires module field_inheritance
 */
class WeeklyRecurringDateTest extends KernelTestBase {

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
   * Tests WeeklyRecurringDate::findWeekdaysBetweenDates().
   */
  public function testFindWeekdaysBetweenDates() {
    // We want to test for generating all the weekdays between Jan 1st and 31st.
    $start_date = new DrupalDateTime('2019-01-01 00:00:00');
    $end_date = new DrupalDateTime('2019-01-30 00:00:00');

    $weekdays = ['Monday', 'Wednesday'];

    $expected_dates = $actual_dates = [];

    $expected_date_objects = [
      'Monday' => [
        new DrupalDateTime('2019-01-07 00:00:00'),
        new DrupalDateTime('2019-01-14 00:00:00'),
        new DrupalDateTime('2019-01-21 00:00:00'),
        new DrupalDateTime('2019-01-28 00:00:00'),
      ],
      'Wednesday' => [
        new DrupalDateTime('2019-01-02 00:00:00'),
        new DrupalDateTime('2019-01-09 00:00:00'),
        new DrupalDateTime('2019-01-16 00:00:00'),
        new DrupalDateTime('2019-01-23 00:00:00'),
        new DrupalDateTime('2019-01-30 00:00:00'),
      ],
    ];

    foreach ($weekdays as $weekday) {
      $date_objects[$weekday] = WeeklyRecurringDate::findWeekdaysBetweenDates($weekday, $start_date, $end_date);
    }

    // Because the objects themselves will be different we convert each of the
    // date time objects into an ISO standard date format for comparison.
    foreach ($expected_date_objects as $weekday => $dates) {
      foreach ($dates as $date) {
        $expected_dates[$weekday][] = $date->format('r');
      }
    }

    foreach ($date_objects as $weekday => $dates) {
      foreach ($dates as $date) {
        $actual_dates[$weekday][] = $date->format('r');
      }
    }

    $this->assertEquals($expected_dates, $actual_dates);
  }

}
