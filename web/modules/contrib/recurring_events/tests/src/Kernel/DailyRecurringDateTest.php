<?php

namespace Drupal\Tests\recurring_events\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\recurring_events\Plugin\Field\FieldType\DailyRecurringDate;

/**
 * @coversDefaultClass \Drupal\recurring_events\Plugin\Field\FieldType\DailyRecurringDate
 * @group recurring_events
 * @requires module field_inheritance
 */
class DailyRecurringDateTest extends KernelTestBase {

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
   * Tests DailyRecurringDate::findDailyDatesBetweenDates().
   */
  public function testFindDailyDatesBetweenDates() {
    // We want to test for generating all the weekdays between Jan 1st and 31st.
    $start_date = new DrupalDateTime('2019-01-01 00:00:00');
    $end_date = new DrupalDateTime('2019-01-07 00:00:00');

    $expected_dates = $dates = [];

    $expected_date_objects = [
      new DrupalDateTime('2019-01-01 00:00:00'),
      new DrupalDateTime('2019-01-02 00:00:00'),
      new DrupalDateTime('2019-01-03 00:00:00'),
      new DrupalDateTime('2019-01-04 00:00:00'),
      new DrupalDateTime('2019-01-05 00:00:00'),
      new DrupalDateTime('2019-01-06 00:00:00'),
      new DrupalDateTime('2019-01-07 00:00:00'),
    ];

    $date_objects = DailyRecurringDate::findDailyDatesBetweenDates($start_date, $end_date);

    // Because the objects themselves will be different we convert each of the
    // date time objects into an ISO standard date format for comparison.
    foreach ($expected_date_objects as $date) {
      $expected_dates[] = $date->format('r');
    }

    foreach ($date_objects as $date) {
      $dates[] = $date->format('r');
    }

    $this->assertEquals($expected_dates, $dates);
  }

}
