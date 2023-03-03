<?php

namespace Drupal\Tests\recurring_events\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\recurring_events\Plugin\Field\FieldType\ConsecutiveRecurringDate;

/**
 * @coversDefaultClass \Drupal\recurring_events\Plugin\Field\FieldType\ConsecutiveRecurringDate
 * @group recurring_events
 * @requires module field_inheritance
 */
class ConsecutiveRecurringDateTest extends KernelTestBase {

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
   * Tests ConsecutiveRecurringDate::findDailyDatesBetweenDates().
   */
  public function testConvertTimeTo24hourFormat() {
    // We want to test for generating all the days between Jan 1st and Jan 7th.
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

    $date_objects = ConsecutiveRecurringDate::findDailyDatesBetweenDates($start_date, $end_date);

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

  /**
   * Tests ConsecutiveRecurringDate::findSlotsBetweenTimes().
   */
  public function testFindSlotsBetweenTimes() {
    // We want to test for generating all the time slots between midnight and
    // 1am with a 10min duration and 5min buffer.
    $start_date = new DrupalDateTime('2019-01-01 00:00:00');

    $form_data = [
      'end_time' => '01:00:00',
      'duration' => '10',
      'duration_units' => 'minute',
      'buffer' => '5',
      'buffer_units' => 'minute',
    ];

    $expected_dates = $dates = [];

    $expected_date_objects = [
      new DrupalDateTime('2019-01-01 00:00:00'),
      new DrupalDateTime('2019-01-01 00:15:00'),
      new DrupalDateTime('2019-01-01 00:30:00'),
      new DrupalDateTime('2019-01-01 00:45:00'),
      new DrupalDateTime('2019-01-01 01:00:00'),
    ];

    $date_objects = ConsecutiveRecurringDate::findSlotsBetweenTimes($start_date, $form_data);

    // Because the objects themselves will be different we convert each of the
    // date time objects into an ISO standard date format for comparison.
    foreach ($expected_date_objects as $date) {
      $expected_dates[] = $date->format('r');
    }

    foreach ($date_objects as $date) {
      $dates[] = $date->format('r');
    }

    $this->assertSame($expected_dates, $dates);
  }

}
