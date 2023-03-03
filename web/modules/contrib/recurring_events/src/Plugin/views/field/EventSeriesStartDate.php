<?php

namespace Drupal\recurring_events\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to show the start date of an event series.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("eventseries_start_date")
 */
class EventSeriesStartDate extends FieldPluginBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $start_date = 'N/A';

    $event = $values->_entity;
    $event_start = $event->getSeriesStart();
    $timezone = new \DateTimeZone(date_default_timezone_get());

    if (!empty($event_start)) {
      $format = \Drupal::config('recurring_events.eventseries.config')->get('date_format');
      $start_date = $this->dateFormatter->format($event_start->getTimestamp(), 'custom', $format, $timezone->getName());
    }
    return $start_date;
  }

}
