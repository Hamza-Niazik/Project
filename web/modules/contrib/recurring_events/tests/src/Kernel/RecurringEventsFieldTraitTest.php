<?php

namespace Drupal\Tests\recurring_events\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\recurring_events\Plugin\RecurringEventsFieldTrait
 * @group recurring_events
 * @requires module field_inheritance
 */
class RecurringEventsFieldTraitTest extends KernelTestBase {

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
   * A reflection of \Drupal\recurring_events\Plugin\RecurringEventsFieldTrait.
   *
   * @var \ReflectionClass
   */
  protected $reflection;

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
    $this->reflection = new \ReflectionClass('\Drupal\recurring_events\Plugin\RecurringEventsFieldTrait');
  }

  /**
   * Tests RecurringEventsFieldTrait::convertTimeTo24hourFormat().
   */
  public function testConvertTimeTo24hourFormat() {
    $method = new \ReflectionMethod('\Drupal\recurring_events\Plugin\RecurringEventsFieldTrait', 'convertTimeTo24hourFormat');
    $method->setAccessible(TRUE);

    $times = [
      '09:30 am' => ['09', '30', '00'],
      '09:30 pm' => ['21', '30', '00'],
      '11:15 am' => ['11', '15', '00'],
      '11:15 pm' => ['23', '15', '00'],
      '12:00 am' => ['00', '00', '00'],
      '12:00 pm' => ['12', '00', '00'],
    ];

    foreach ($times as $time => $expected) {
      $result = $method->invoke($this->reflection, $time);
      $this->assertEquals($expected, $result);
    }
  }

}
