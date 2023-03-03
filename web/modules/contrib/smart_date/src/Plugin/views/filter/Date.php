<?php

namespace Drupal\smart_date\Plugin\views\filter;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\views\FieldAPIHandlerTrait;
use Drupal\views\Plugin\views\filter\Date as CoreDate;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Date/time views filter, with granularity patch applied.
 *
 * Even thought dates are stored as strings, the numeric filter is extended
 * because it provides more sensible operators.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("date")
 */
class Date extends CoreDate implements ContainerFactoryPluginInterface {

  use FieldAPIHandlerTrait;

  /**
   * The request stack used to determine current time.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new Date handler.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to determine the current time.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatterInterface $date_formatter, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dateFormatter = $date_formatter;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter'),
      $container->get('request_stack')
    );
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    // value is already set up properly, we're just adding our new field to it.
    $options['value']['contains']['granularity']['default'] = 'second';

    return $options;
  }

  /**
   * Add a granularity selector to the value form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $options = [
      'second' => $this->t('Second'),
      'minute' => $this->t('Minute'),
      'hour'   => $this->t('Hour'),
      'day'    => $this->t('Day'),
      'month'  => $this->t('Month'),
      'year'   => $this->t('Year'),
    ];

    $form['value']['granularity'] = [
      '#type' => 'radios',
      '#title' => $this->t('Granularity'),
      '#options' => $options,
      '#description' => $this->t('The granularity is the smallest unit to use when determining whether two dates are the same; for example, if the granularity is "Year" then all dates in 1999, regardless of when they fall in 1999, will be considered the same date.'),
      '#default_value' => $this->options['value']['granularity'],
      '#weight' => 10,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $granularity = $this->options['value']['granularity'];
    // Set the date format based on granularity.
    if (isset($this->dateFormats[$granularity])) {
      $this->dateFormat = $this->dateFormats[$granularity];
    }

    parent::query();
  }

  /**
   * Override parent method, which deals with dates as integers.
   */
  protected function opBetween($field) {
    $timezone = $this->getTimezone();
    $granularity = $this->options['value']['granularity'];

    // Convert value to DateTimePlus for additional processing.
    $a = new DateTimePlus($this->value['min'], new \DateTimeZone($timezone));
    $b = new DateTimePlus($this->value['max'], new \DateTimeZone($timezone));
    // Granularity requires some conversion.
    if ($granularity != 'second') {
      $min = [
        'year' => $a->format('Y'),
        'month' => $a->format('n'),
        'day' => $a->format('j'),
        'hour' => $a->format('G'),
        'minute' => $a->format('i'),
        'second' => $a->format('s'),
      ];
      $max = [
        'year' => $b->format('Y'),
        'month' => $b->format('n'),
        'day' => $b->format('j'),
        'hour' => $b->format('G'),
        'minute' => $b->format('i'),
        'second' => $b->format('s'),
      ];
      switch ($granularity) {
        case 'year':
          $min['month'] = '01';
          $max['month'] = '12';
          $max['day'] = '31';
        case 'month':
          $min['day'] = '01';
          if ($granularity != 'year') {
            $max['day'] = $b->format('t');
          }
        case 'day':
          $min['hour'] = '00';
          $max['hour'] = '23';
        case 'hour':
          $min['minute'] = '00';
          $max['minute'] = '59';
        case 'minute':
          $min['second'] = '00';
          $max['second'] = '59';
      }
      // Update the range with our altered values.
      $a = $a->createFromArray($min);
      $b = $b->createFromArray($max);
    }

    // This is safe because we forced the provided values to DateTimePlus.
    $operator = strtoupper($this->operator);
    $start = $a->format('U');
    $end = $b->format('U');
    $this->query->addWhereExpression($this->options['group'], "$field $operator $start AND $end");
  }

  /**
   * Override parent method, to add granularity options.
   */
  protected function opSimple($field) {
    $timezone = $this->getTimezone();
    $granularity = $this->options['value']['granularity'];

    // Convert value to DateTimePlus for additional processing.
    $date_value = $this->value['value'];
    $value = new DateTimePlus($date_value, new \DateTimeZone($timezone));
    // Granularity requires some conversion.
    if ($granularity != 'second') {
      $value_array = [
        'year' => $value->format('Y'),
        'month' => $value->format('n'),
        'day' => $value->format('j'),
        'hour' => $value->format('G'),
        'minute' => $value->format('i'),
        'second' => $value->format('s'),
      ];
      $min = $max = $value_array;
      switch ($granularity) {
        case 'year':
          $min['month'] = '01';
          $max['month'] = '12';
          $max['day'] = '31';
        case 'month':
          $min['day'] = '01';
          if ($granularity != 'year') {
            $max['day'] = $value->format('t');
          }
        case 'day':
          $min['hour'] = '00';
          $max['hour'] = '23';
        case 'hour':
          $min['minute'] = '00';
          $max['minute'] = '59';
        case 'minute':
          $min['second'] = '00';
          $max['second'] = '59';
      }

      // Additional, operator-specific logic.
      if (substr($this->operator, 0, 1) == '>') {
        $value = $value->createFromArray($min, $timezone);
      }
      elseif (substr($this->operator, 0, 1) == '<') {
        $value = $value->createFromArray($max, $timezone);
      }
      else {
        $min_value = $value->createFromArray($min, $timezone)->format('U');
        $max_value = $value->createFromArray($max, $timezone)->format('U');
        if ($this->operator == '=') {
          $operator = 'BETWEEN';
        }
        elseif ($this->operator == '!=') {
          $operator = 'NOT BETWEEN';
        }
        $this->query->addWhereExpression($this->options['group'], "$field $operator $min_value AND $max_value");
        return;
      }
    }

    // This is safe because we forced the provided value to a DateTimePlus.
    $this->query->addWhereExpression($this->options['group'], "$field $this->operator " . $value->format('U'));
  }

  /**
   * Get the proper time zone to use in computations.
   *
   * Date-only fields do not have a time zone associated with them, so the
   * filter input needs to use UTC for reference. Otherwise, use the time zone
   * for the current user.
   *
   * @return string
   *   The time zone name.
   */
  protected function getTimezone() {
    return date_default_timezone_get();
  }

}
