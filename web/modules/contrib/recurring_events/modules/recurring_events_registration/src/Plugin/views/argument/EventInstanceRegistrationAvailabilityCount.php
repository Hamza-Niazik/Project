<?php

namespace Drupal\recurring_events_registration\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept an availability count.
 *
 * @ViewsArgument("eventinstance_registration_availability_count")
 */
class EventInstanceRegistrationAvailabilityCount extends NumericArgument {

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
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    // Set -1 as the default value so that if no events match the checks, then
    // we should get no results, rather than all results.
    $items = ['-1'];
    $table = $this->ensureMyTable();

    // Grab the current view being executed.
    $view = clone $this->query->view;
    $filters = $view->argument;
    // Remove any instances of this filter from the filters.
    if (!empty($filters)) {
      foreach ($filters as $key => $filter) {
        if ($filter instanceof \Drupal\recurring_events_registration\Plugin\views\argument\EventInstanceRegistrationAvailabilityCount) {
          unset($view->argument[$key]);
        }
      }
    }
    // Execute the current view with the filters removed, so we can reduce the
    // number of event instances we need to examine to find their availability.
    // This makes the query more efficient and avoids having to do messy union
    // selects across multiple tables to determine the availability of an event.
    $view->preExecute();
    $view->execute();

    if (!empty($this->options['break_phrase'])) {
      $break = static::breakString($this->argument, FALSE);
      $this->value = $break->value;
      $this->operator = $break->operator;
    } else {
      $this->value = [$this->argument];
    }

    if (!empty($view->result)) {
      // Loop through results, evaluate result's availability re: filter settings.
      foreach ($view->result as $key => $result) {
        $availability = $result->_entity->availability_count->getValue()[0]['value'] ?? -1;

        $filter_result = FALSE;

        if (count($this->value) > 1) {
          $filter_result = array_search($availability, $this->value) !== FALSE;
        }
        else {
          $filter_result = $availability == $this->argument;
        }

        if (!empty($this->options['not'])) {
          $filter_result = !$filter_result;
        }

        if ($filter_result) {
          $items[] = $result->_entity->id();
        }
      }
    }

    // Filter this view by the events which match the availability above.
    $items = implode(',', $items);
    $this->query->addWhereExpression(0, "$table.id IN (" . $items . ")");
  }

}
