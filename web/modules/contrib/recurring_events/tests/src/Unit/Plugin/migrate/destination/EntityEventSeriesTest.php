<?php

namespace Drupal\Tests\recurring_events\Unit\Plugin\migrate\destination;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\migrate\Row;
use Drupal\recurring_events\Plugin\migrate\destination\EntityEventSeries;
use Drupal\Tests\migrate\Unit\Plugin\migrate\destination\EntityTestBase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\recurring_events\Plugin\migrate\destination\EntityEventSeries
 * @group recurring_events
 */
class EntityEventSeriesTest extends EntityTestBase {

  const SAVED_NEW = 1;
  const TEST_ENTITY_ID = 8;

  /**
   * Mocked field type plugin manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * Mocked account switcher service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * {@inheritdoc}
   *
   * @noinspection PhpParamsInspection
   * @noinspection PhpUndefinedMethodInspection
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fieldTypeManager = $this->prophesize(FieldTypePluginManagerInterface::class);
    $this->accountSwitcher = $this->prophesize(AccountSwitcherInterface::class);

    // Handle calls to methods on mocked services in the parent class.
    $this->entityType->getKey('id')->willReturn('id');
    $this->entityType->getKey('bundle')->willReturn('type');

    // Configure the mocked entity storage to create mocked entities.
    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->isValidationRequired()->willReturn(FALSE);
    $entity->validate()->willReturn([]);
    $entity->save()->willReturn(self::SAVED_NEW);
    $entity->id()->willReturn(self::TEST_ENTITY_ID);
    $entity->enforceIsNew()->willReturn($entity->reveal());
    $this->storage->create(Argument::type('array'))->willReturn($entity->reveal());

    // Set a mocked container for non-injected services called by the plugin.
    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn('en');
    $language_manager = $this->prophesize(LanguageManagerInterface::class);
    $language_manager->getCurrentLanguage()->willReturn($language->reveal());
    $container = new ContainerBuilder();
    $container->set('language_manager', $language_manager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Helper method to create an EntityEventSeries object with mocked services.
   *
   * @param array $configuration
   *   Configuration for the destination plugin.
   *
   * @return \Drupal\recurring_events\Plugin\migrate\destination\EntityEventSeries
   *   An EntityEventSeries destination plugin with mocked services.
   *
   * @noinspection PhpParamsInspection
   */
  protected function getEntityEventSeries(array $configuration = []): EntityEventSeries {
    return new EntityEventSeries(
      $configuration,
      'entity:eventseries',
      [],
      $this->migration->reveal(),
      $this->storage->reveal(),
      [],
      $this->entityFieldManager->reveal(),
      $this->fieldTypeManager->reveal(),
      $this->accountSwitcher->reveal()
    );
  }

  /**
   * Tests importing from a D7 source_date_field.
   *
   * @covers ::import
   */
  public function testImportSourceDateField() {
    $plugin = $this->getEntityEventSeries([
      'default_bundle' => 'default',
      'source_date_field' => 'field_event_datetime',
      'source_timezone' => 'America/New_York',
    ]);

    // Import a mocked D7 date field.
    $values = [
      'field_event_datetime' => [
        [
          'value' => '2022-07-19T12:00:00',
          'value2' => '2022-07-19T13:00:00',
          'rrule' => 'RRULE:FREQ=WEEKLY',
        ],
        [
          'value' => '2022-08-02T12:00:00',
          'value2' => '2022-08-02T13:00:00',
        ],
      ],
    ];
    $row = new Row($values);
    $row->setDestinationProperty('type', 'default');
    $ids = $plugin->import($row);
    $this->assertEquals([self::TEST_ENTITY_ID], $ids);

    // Assert that it added destination properties for the recurrence pattern.
    $destination = $row->getDestination();
    $this->assertArrayHasKey('recur_type', $destination);
    $this->assertArrayHasKey('weekly_recurring_date', $destination);
  }

  /**
   * Tests importing using the 'recurring_date_field' option.
   *
   * @covers ::import
   */
  public function testImportRecurringDateField() {
    $plugin = $this->getEntityEventSeries([
      'default_bundle' => 'default',
      'recurring_date_field' => 'dates',
    ]);

    // Import custom date data.
    $row = new Row([]);
    $row->setDestinationProperty('type', 'default');
    $row->setDestinationProperty('recur_type', 'custom');
    $row->setDestinationProperty('dates', [
      [
        'value' => '2019-09-05T09:00:00',
        'end_value' => '2019-09-05T18:00:00',
      ],
    ]);
    $ids = $plugin->import($row);
    $this->assertEquals([self::TEST_ENTITY_ID], $ids);

    // Assert that it added the destination property for the recurrence pattern.
    $destination = $row->getDestination();
    $this->assertArrayHasKey('custom_date', $destination);
  }

  /**
   * Tests that the import can run without arguments.
   *
   * @covers ::import
   */
  public function testImportNoSourceDateField() {
    $plugin = $this->getEntityEventSeries([
      'default_bundle' => 'default',
    ]);

    $row = new Row([]);
    $row->setDestinationProperty('type', 'default');
    $ids = $plugin->import($row);
    $this->assertEquals([self::TEST_ENTITY_ID], $ids);
  }

}
