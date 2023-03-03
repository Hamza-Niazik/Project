<?php

namespace Drupal\recurring_events_registration\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\NumericFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter handler to show the availability of registrations for event instances.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("eventinstance_registration_availability_count")
 */
class EventInstanceRegistrationAvailabilityCount extends NumericFilter {

  /**
   * Constructs a new EventInstanceRegistrationAvailability object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('recurring_events_registration.creation_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Set -1 as the default value so that if no events match the checks, then
    // we should get no results, rather than all results.
    $items = ['-1'];
    $table = $this->ensureMyTable();

    // Grab the current view being executed.
    $view = clone $this->query->view;
    $filters = $view->filter;
    // Remove any instances of this filter from the filters.
    if (!empty($filters)) {
      foreach ($filters as $key => $filter) {
        if ($filter instanceof EventInstanceRegistrationAvailabilityCount) {
          unset($view->filter[$key]);
        }
      }
    }
    // Execute the current view with the filters removed, so we can reduce the
    // number of event instances we need to examine to find their availability.
    // This makes the query more efficient and avoids having to do messy union
    // selects across multiple tables to determine the availability of an event.
    $view->preExecute();
    $view->execute();

    $value = $this->value['value'] ?? NULL;
    $min = $this->value['min'] ?? NULL;
    $max = $this->value['max'] ?? NULL;

    if (!empty($view->result)) {
      // Loop through results, evaluate result's availability re: filter settings.
      foreach ($view->result as $key => $result) {
        $availability = (int) $result->_entity->availability_count->getValue()[0]['value'] ?? -1;

        $filter_result = FALSE;

        switch ($this->operator) {
          case '<':
            $filter_result = ($availability === -1) ? FALSE : $availability < $value;
            break;
          case '<=':
            $filter_result = ($availability === -1) ? FALSE : $availability <= $value;
            break;
          case '=':
            $filter_result = $availability == $value;
            break;
          case '!=':
            $filter_result = $availability != $value;
            break;
          case '>=':
            $filter_result = ($availability === -1) ? TRUE : $availability >= $value;
            break;
          case '>':
            $filter_result = ($availability === -1) ? TRUE : $availability > $value;
            break;
          case 'between':
            $filter_result = ($availability === -1) ? FALSE : ($min <= $availability && $availability <= $max);
            break;
          case 'not between':
            $filter_result = ($availability === -1) ? TRUE : !($min <= $availability && $availability <= $max);
            break;
          case 'regular_expression':
            $filter_result = preg_match($value, $availability);
            break;
          case 'empty':
            $filter_result = ($availability === -1) ? FALSE : !$availability;
            break;
          case 'not empty':
            $filter_result = ($availability === -1) ? TRUE : !!$availability;
            break;
        }

        if ($filter_result) {
          $items[] = $result->_entity->id();
        }
      }
    }

    // Filter this view by the events which match the availability above.
    $items = implode(',', $items);
    $this->query->addWhereExpression($this->options['group'], "$table.id IN (" . $items . ")");
  }

}
